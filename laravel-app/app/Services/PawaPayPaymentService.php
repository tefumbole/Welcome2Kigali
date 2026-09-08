<?php

namespace App\Services;

use App\CashRegister;
use App\MembershipPayment;
use App\Order;
use App\PawaPayDeposit;
use App\Payment;
use App\Sale;
use App\Support\MomoNetwork;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PawaPayPaymentService
{
    protected $api;

    public function __construct(PawaPayService $api)
    {
        $this->api = $api;
    }

    public function isConfigured()
    {
        return $this->api->isConfigured();
    }

    /**
     * @return array {ok: bool, deposit?: PawaPayDeposit, message?: string}
     */
    public function start($purpose, $purposeId, $amount, $phone, $networkHint, $description, $redirectUrl, array $extra = [])
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'Mobile money is not configured. Add PAWAPAY_API_TOKEN to .env.'];
        }

        $msisdn = MomoNetwork::normalize($phone);
        if ($msisdn === '' || strlen($msisdn) < 10) {
            return ['ok' => false, 'message' => 'Please enter a valid MoMo phone number (e.g. 0793…).'];
        }

        $predicted = $this->api->predictProvider($msisdn);
        $provider = $predicted ?: MomoNetwork::provider($msisdn, $networkHint);
        $currency = isset($extra['currency']) ? $extra['currency'] : MomoNetwork::currencyForPhone($msisdn);
        $depositId = (string) Str::uuid();
        $intAmount = (string) (int) round($amount);
        if ((int) $intAmount < 1) {
            return ['ok' => false, 'message' => 'Payment amount must be at least 1.'];
        }

        $payload = [
            'depositId' => $depositId,
            'amount' => $intAmount,
            'currency' => $currency,
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'phoneNumber' => $msisdn,
                    'provider' => $provider,
                ],
            ],
            'clientReferenceId' => $purpose.'-'.$purposeId,
            'customerMessage' => $this->api->customerMessage($description),
            'metadata' => [
                ['purpose' => (string) $purpose],
                ['purposeId' => (string) $purposeId],
            ],
        ];

        $response = $this->api->createDeposit($payload);
        $status = $this->api->extractStatus($response);
        if (! $response || $this->api->isFailureStatus($status) || (! $this->api->isAcceptedStatus($status) && ! $this->api->isSuccessStatus($status))) {
            $failure = isset($response['failureReason']['failureMessage']) ? $response['failureReason']['failureMessage'] : null;
            Log::warning('PawaPay deposit rejected', ['status' => $status, 'body' => $response]);

            return [
                'ok' => false,
                'message' => $failure ?: 'Could not start Mobile Money. Check the number and try again.',
            ];
        }

        $row = PawaPayDeposit::create([
            'deposit_id' => $depositId,
            'purpose' => $purpose,
            'purpose_id' => (int) $purposeId,
            'extra_ids' => ! empty($extra['ids']) ? json_encode($extra['ids']) : null,
            'amount' => $amount,
            'currency' => $currency,
            'phone' => $msisdn,
            'provider' => $provider,
            'paying_method' => MomoNetwork::payingMethodLabel($provider),
            'status' => $this->api->isSuccessStatus($status) ? 'completed' : 'pending',
            'raw_status' => $status,
            'redirect_url' => $redirectUrl,
            'user_id' => Auth::id(),
        ]);

        if ($purpose === 'membership') {
            MembershipPayment::where('id', $purposeId)->update([
                'pawapay_deposit_id' => $depositId,
                'method' => 'PawaPay',
            ]);
        }

        if ($row->status === 'completed') {
            $this->apply($row);
        }

        return ['ok' => true, 'deposit' => $row];
    }

    public function refreshAndApply($depositId)
    {
        $row = PawaPayDeposit::where('deposit_id', $depositId)->first();
        if (! $row) {
            return null;
        }
        $remote = $this->api->getDeposit($depositId);
        $status = $this->api->extractStatus($remote);
        $this->syncStatus($row, $status);

        return $row->fresh();
    }

    public function handleCallback(array $payload)
    {
        $depositId = $this->api->extractDepositId($payload);
        if (! $depositId) {
            return null;
        }
        $row = PawaPayDeposit::where('deposit_id', $depositId)->first();
        if (! $row) {
            Log::info('PawaPay callback for unknown deposit', ['deposit_id' => $depositId]);

            return null;
        }
        $status = $this->api->extractStatus($payload);
        $this->syncStatus($row, $status);

        return $row->fresh();
    }

    protected function syncStatus(PawaPayDeposit $row, $status)
    {
        $row->raw_status = $status;
        if ($this->api->isSuccessStatus($status)) {
            $row->status = 'completed';
            $row->save();
            $this->apply($row);
        } elseif ($this->api->isFailureStatus($status)) {
            $row->status = 'failed';
            $row->save();
            if ($row->purpose === 'membership') {
                MembershipPayment::where('id', $row->purpose_id)->update(['status' => 'failed']);
            }
        } else {
            $row->status = 'pending';
            $row->save();
        }
    }

    public function apply(PawaPayDeposit $row)
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
            Log::error('PawaPay apply failed: '.$e->getMessage(), ['deposit_id' => $row->deposit_id]);
        }

        return $row;
    }

    protected function applyMembership(PawaPayDeposit $row)
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

    protected function applySale(PawaPayDeposit $row)
    {
        $sale = Sale::find($row->purpose_id);
        if (! $sale) {
            return;
        }
        $already = Payment::where('sale_id', $sale->id)
            ->where('paying_method', $row->paying_method)
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
        $payment->payment_reference = 'momo-'.date('Ymd-His').'-'.$sale->id;
        $payment->amount = $row->amount;
        $payment->change = 0;
        $payment->paying_method = $row->paying_method ?: 'MTN MoMo';
        $payment->payment_note = 'PawaPay '.$row->deposit_id;
        $payment->save();
        $sale->save();
    }

    protected function applyOrders(PawaPayDeposit $row)
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
            $order->payment_method = $row->paying_method ?: 'MTN';
            $order->save();
        }
    }

    protected function applyBooking(PawaPayDeposit $row)
    {
        $booking = \App\Booking::find($row->purpose_id);
        if (! $booking) {
            return;
        }
        $booking->payment_status = 4;
        $booking->paid_amount = $booking->grand_total;
        $booking->payment_method = $row->paying_method ?: 'MTN';
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
