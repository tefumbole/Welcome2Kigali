<?php

namespace App\Http\Controllers;

use App\MomoPayment;
use App\Support\MomoUssd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class MomoPayController extends Controller
{
    public function show($purpose, $id, $amount, $token)
    {
        if (! MomoUssd::isValid($purpose, $id, $amount, $token)) {
            abort(404);
        }

        $amount = MomoUssd::amount($amount);
        $reference = $this->reference($purpose, $id);
        $record = $this->record($purpose, $id, $amount, $reference);
        $paid = $this->isPaid($purpose, $id, $record);

        return view('beyond.payments.momo_ussd', [
            'purpose' => $purpose,
            'id' => $id,
            'token' => $token,
            'reference' => $reference,
            'amount' => $amount,
            'tel' => MomoUssd::telUri($amount),
            'qr' => MomoUssd::qrDataUri($amount),
            'paid' => $paid,
            'backUrl' => request()->query('back'),
        ]);
    }

    public function markPaid(Request $request, $purpose, $id, $amount, $token)
    {
        if (! MomoUssd::isValid($purpose, $id, $amount, $token)) {
            abort(404);
        }

        $amount = MomoUssd::amount($amount);
        $this->applyPaid($purpose, $id, $amount);

        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            $track = $this->trackUrlFor($purpose, $id);

            return response()->json([
                'ok' => true,
                'paid' => true,
                'purpose' => $purpose,
                'id' => (int) $id,
                'amount' => $amount,
                'track_url' => $track,
                'pay_url' => MomoUssd::payUrl($purpose, $id, $amount),
            ]);
        }

        $back = $request->query('back');

        // POS sale: send the teller straight to the printable invoice; the POS
        // cart was already consumed when the sale was created.
        if ($purpose === 'sale' && $back && \Illuminate\Support\Str::contains($back, '/pos') && auth()->check()) {
            return redirect()->to(url('sales/gen_invoice/'.$id))->with('message', 'Payment received by MoMo.');
        }

        // Frontend cafe order: after guest confirms MoMo, go to order tracking when available.
        $track = $this->trackUrlFor($purpose, $id);
        if ($track && in_array($purpose, ['sale', 'order'], true) && (! $back || \Illuminate\Support\Str::contains((string) $back, '/menu'))) {
            return redirect()->to($track)->with('momo_marked_paid', true);
        }

        $target = MomoUssd::payUrl($purpose, $id, $amount);
        if ($back && \Illuminate\Support\Str::startsWith($back, url('/'))) {
            $target .= '?back='.urlencode($back);
        }

        return redirect()->to($target)->with('momo_marked_paid', true);
    }

    /** Mark a Sale paid from the public order API (guest confirmed MoMo). */
    public function confirmSalePaid(\App\Sale $sale, $amount)
    {
        $this->applyPaid('sale', $sale->id, MomoUssd::amount($amount));
    }

    protected function applyPaid($purpose, $id, $amount)
    {
        $reference = $this->reference($purpose, $id);
        $record = $this->record($purpose, $id, $amount, $reference);

        if ($record && ! $record->isPaid()) {
            $record->status = 'paid';
            $record->paid_at = now();
            $record->save();
        }

        $this->finalize($purpose, $id, $amount);
    }

    protected function trackUrlFor($purpose, $id)
    {
        try {
            if ($purpose === 'sale') {
                $order = \App\RestaurantOrder::where('sale_id', $id)->first();

                return $order ? $order->trackUrl() : null;
            }
            if ($purpose === 'order') {
                $order = \App\RestaurantOrder::where('checkout_token', 'web-order-'.$id)->first();

                return $order ? $order->trackUrl() : null;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return null;
    }

    protected function record($purpose, $id, $amount, $reference)
    {
        if (! Schema::hasTable('momo_payments')) {
            return null;
        }

        $record = MomoPayment::where('purpose', $purpose)->where('ref_id', $id)->first();
        if (! $record) {
            $record = MomoPayment::create([
                'purpose' => $purpose,
                'ref_id' => $id,
                'amount' => $amount,
                'reference' => $reference,
                'status' => 'pending',
            ]);
        }

        return $record;
    }

    protected function isPaid($purpose, $id, $record): bool
    {
        if ($record && $record->isPaid()) {
            return true;
        }

        // Also treat the link as paid when the underlying record was completed elsewhere.
        try {
            switch ($purpose) {
                case 'sale':
                    return (int) optional(\App\Sale::find($id))->payment_status === 4;
                case 'order':
                    return (int) optional(\App\Order::find($id))->payment_status === 1;
                case 'membership':
                    return optional(\App\MembershipPayment::find($id))->status === 'paid';
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return false;
    }

    protected function finalize($purpose, $id, $amount)
    {
        try {
            switch ($purpose) {
                case 'sale':
                    $sale = \App\Sale::find($id);
                    if ($sale && (int) $sale->payment_status !== 4) {
                        $sale->paid_amount = $sale->grand_total;
                        $sale->payment_status = 4;
                        $sale->save();
                        try {
                            $account = \App\Account::where('is_default', true)->where('is_active', true)->first()
                                ?: \App\Account::where('is_active', true)->orderBy('id')->first();
                            $payment = new \App\Payment();
                            $payment->user_id = auth()->id() ?: $sale->user_id;
                            $payment->sale_id = $sale->id;
                            $payment->account_id = $account ? $account->id : null;
                            $payment->payment_reference = 'mpr-'.date('Ymd').'-'.date('his');
                            $payment->amount = $amount;
                            $payment->change = 0;
                            $payment->paying_method = 'MTN Mobile Money';
                            $payment->save();
                        } catch (\Throwable $e) {
                            \Log::warning('[momo-ussd] sale payment row failed: '.$e->getMessage());
                        }
                    }
                    break;
                case 'order':
                    $order = \App\Order::find($id);
                    if ($order && (int) $order->payment_status !== 1) {
                        $order->payment_status = 1;
                        $order->save();
                    }
                    if ($order) {
                        try {
                            app(\App\Services\Restaurant\RestaurantOrderService::class)->markWebsiteOrderPaid($order);
                        } catch (\Throwable $e) {
                            \Log::warning('[momo-ussd] website order paid link failed: '.$e->getMessage());
                        }
                    }
                    break;
                case 'membership':
                    $payment = \App\MembershipPayment::find($id);
                    if ($payment && $payment->status !== 'paid') {
                        $service = app(\App\Services\MembershipService::class);
                        $membership = $service->activateFromPayment($payment);
                        try {
                            app(\App\Services\MembershipNotifier::class)->approved($membership);
                        } catch (\Throwable $e) {
                            // notification is best-effort
                        }
                    }
                    break;
            }
        } catch (\Throwable $e) {
            \Log::warning('[momo-ussd] finalize failed: '.$e->getMessage());
        }
    }

    protected function reference($purpose, $id)
    {
        switch ($purpose) {
            case 'sale':
                return optional(\App\Sale::find($id))->reference_no;
            case 'order':
                return 'Order #'.$id;
            case 'membership':
                return optional(optional(\App\MembershipPayment::find($id))->membership)->number;
            default:
                return null;
        }
    }
}
