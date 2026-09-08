<?php

namespace App\Services;

use App\CashRegister;
use App\MembershipPayment;
use App\Order;
use App\Payment;
use App\Sale;
use App\StripeCheckout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StripePaymentService
{
    protected $api;

    public function __construct(StripeService $api)
    {
        $this->api = $api;
    }

    public function isConfigured()
    {
        return $this->api->isConfigured();
    }

    public function isTestMode()
    {
        return $this->api->isTestMode();
    }

    /**
     * @return array {ok: bool, url?: string, checkout?: StripeCheckout, message?: string}
     */
    public function start($purpose, $purposeId, $amount, $description, $redirectUrl, array $extra = [])
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'Card payments are not configured yet.'];
        }

        $intAmount = (int) round($amount);
        if ($intAmount < 1) {
            return ['ok' => false, 'message' => 'Payment amount must be at least 1.'];
        }

        $successUrl = url('/payments/stripe/success').'?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = url('/payments/stripe/cancel').'?session_id={CHECKOUT_SESSION_ID}';

        try {
            $session = $this->api->createCheckoutSession([
                'amount' => $amount,
                'name' => $description,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'client_reference_id' => $purpose.'-'.$purposeId,
                'customer_email' => isset($extra['email']) ? $extra['email'] : null,
                'metadata' => [
                    'purpose' => (string) $purpose,
                    'purpose_id' => (string) $purposeId,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Stripe checkout create failed: '.$e->getMessage());
            $raw = $e->getMessage();
            $hint = 'Could not start Visa payment. Please try again.';
            if (stripos($raw, 'currency') !== false) {
                $hint = 'Stripe rejected the currency. Enable RWF on the Stripe account, or set STRIPE_CURRENCY.';
            } elseif (stripos($raw, 'at least') !== false || stripos($raw, 'amount') !== false) {
                $hint = $raw;
            }

            return ['ok' => false, 'message' => $hint];
        }

        $row = StripeCheckout::create([
            'session_id' => $session->id,
            'purpose' => $purpose,
            'purpose_id' => (int) $purposeId,
            'extra_ids' => ! empty($extra['ids']) ? json_encode($extra['ids']) : null,
            'amount' => $amount,
            'currency' => $this->api->currency(),
            'paying_method' => 'Visa',
            'status' => 'pending',
            'redirect_url' => $redirectUrl,
            'user_id' => Auth::id(),
        ]);

        if ($purpose === 'membership') {
            MembershipPayment::where('id', $purposeId)->update([
                'stripe_session_id' => $session->id,
                'method' => 'Visa',
            ]);
        }

        return ['ok' => true, 'url' => $session->url, 'checkout' => $row];
    }

    public function completeFromSessionId($sessionId)
    {
        $row = StripeCheckout::where('session_id', $sessionId)->first();
        if (! $row) {
            return null;
        }
        if ($row->applied_at || $row->status === 'completed') {
            return $row;
        }

        try {
            $session = $this->api->retrieveSession($sessionId);
        } catch (\Throwable $e) {
            Log::error('Stripe session retrieve failed: '.$e->getMessage(), ['session_id' => $sessionId]);

            return $row;
        }

        if ($this->api->sessionIsPaid($session)) {
            $row->status = 'completed';
            $row->save();
            $this->apply($row);
        }

        return $row->fresh();
    }

    public function markCanceled($sessionId)
    {
        $row = StripeCheckout::where('session_id', $sessionId)->first();
        if (! $row || $row->status === 'completed') {
            return $row;
        }
        $row->status = 'canceled';
        $row->save();
        if ($row->purpose === 'membership') {
            MembershipPayment::where('id', $row->purpose_id)->update(['status' => 'failed']);
        }

        return $row;
    }

    public function handleWebhook($payload, $sigHeader)
    {
        try {
            $event = $this->api->constructWebhookEvent($payload, $sigHeader);
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook rejected: '.$e->getMessage());

            return null;
        }

        $type = is_object($event) ? $event->type : (isset($event['type']) ? $event['type'] : '');
        $object = is_object($event) ? $event->data->object : (isset($event['data']['object']) ? $event['data']['object'] : null);
        $sessionId = '';
        if (is_object($object) && isset($object->id)) {
            $sessionId = $object->id;
        } elseif (is_array($object) && isset($object['id'])) {
            $sessionId = $object['id'];
        }
        if ($sessionId === '') {
            return null;
        }

        if ($type === 'checkout.session.completed' || $type === 'checkout.session.async_payment_succeeded') {
            return $this->completeFromSessionId($sessionId);
        }
        if ($type === 'checkout.session.async_payment_failed' || $type === 'checkout.session.expired') {
            return $this->markCanceled($sessionId);
        }

        return null;
    }

    public function apply(StripeCheckout $row)
    {
        if ($row->applied_at) {
            return $row;
        }
        if ($row->status !== 'completed') {
            return $row;
        }

        try {
            if ($row->purpose === 'membership') {
                $this->applyMembership($row);
            } elseif ($row->purpose === 'sale') {
                $this->applySale($row);
            } elseif ($row->purpose === 'order') {
                $this->applyOrders($row);
            } elseif ($row->purpose === 'booking') {
                $this->applyBooking($row);
            }
            $row->applied_at = now();
            $row->save();
        } catch (\Throwable $e) {
            Log::error('Stripe apply failed: '.$e->getMessage(), ['session_id' => $row->session_id]);
        }

        return $row;
    }

    protected function applyMembership(StripeCheckout $row)
    {
        $payment = MembershipPayment::find($row->purpose_id);
        if (! $payment) {
            return;
        }
        $memberships = app(MembershipService::class);
        $membership = $memberships->activateFromPayment($payment);
        try {
            app(MembershipNotifier::class)->approved($membership);
        } catch (\Throwable $e) {
        }
    }

    protected function applySale(StripeCheckout $row)
    {
        $sale = Sale::find($row->purpose_id);
        if (! $sale) {
            return;
        }
        $already = Payment::where('sale_id', $sale->id)
            ->where('paying_method', 'Visa')
            ->where('amount', $row->amount)
            ->where('created_at', '>=', $row->created_at)
            ->first();
        if ($already) {
            return;
        }

        $sale->paid_amount = $sale->paid_amount + $row->amount;
        $balance = $sale->grand_total - $sale->paid_amount;
        $sale->payment_status = $this->salePaymentStatus($sale->paid_amount, $balance);

        $cashRegister = CashRegister::where('user_id', $row->user_id ?: $sale->user_id)
            ->where('warehouse_id', $sale->warehouse_id)
            ->where('status', true)
            ->first();

        $payment = new Payment();
        $payment->user_id = $row->user_id ?: $sale->user_id;
        $payment->sale_id = $sale->id;
        if ($cashRegister) {
            $payment->cash_register_id = $cashRegister->id;
        }
        $payment->account_id = 1;
        $payment->payment_reference = 'visa-'.date('Ymd-His').'-'.$sale->id;
        $payment->amount = $row->amount;
        $payment->change = 0;
        $payment->paying_method = 'Visa';
        $payment->payment_note = 'Stripe '.$row->session_id;
        $payment->save();
        $sale->save();
    }

    protected function applyOrders(StripeCheckout $row)
    {
        $ids = $row->extraIds();
        if (empty($ids)) {
            $ids = [$row->purpose_id];
        }
        foreach ($ids as $id) {
            $order = Order::find($id);
            if (! $order) {
                continue;
            }
            $order->payment_status = 1;
            $order->payment_method = 'Visa';
            $order->save();
        }
    }

    protected function applyBooking(StripeCheckout $row)
    {
        $booking = \App\Booking::find($row->purpose_id);
        if (! $booking) {
            return;
        }
        $booking->payment_status = 4;
        $booking->paid_amount = $booking->grand_total;
        $booking->payment_method = 'Visa';
        $booking->save();
    }

    protected function salePaymentStatus($paid, $balance)
    {
        if ($paid <= 0) {
            return 1;
        }
        if ($balance > 0) {
            return 3;
        }

        return 4;
    }
}
