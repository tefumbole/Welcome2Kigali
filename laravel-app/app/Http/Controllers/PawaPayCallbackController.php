<?php

namespace App\Http\Controllers;

use App\PawaPayDeposit;
use App\Services\PawaPayPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class PawaPayCallbackController extends Controller
{
    public function handle(Request $request, PawaPayPaymentService $payments)
    {
        $payload = $request->all();
        Log::info('PawaPay callback', [
            'deposit_id' => $payload['depositId'] ?? null,
            'status' => $payload['status'] ?? null,
        ]);
        $payments->handleCallback($payload);

        return response()->json(['status' => 'ok'], 200);
    }

    public function wait($depositId)
    {
        $deposit = PawaPayDeposit::where('deposit_id', $depositId)->firstOrFail();

        return view('beyond.payments.pawapay_wait', compact('deposit'));
    }

    public function status($depositId, PawaPayPaymentService $payments)
    {
        $deposit = $payments->refreshAndApply($depositId);
        if (! $deposit) {
            return response()->json(['status' => 'missing'], 404);
        }

        $redirect = $deposit->redirect_url ?: url('/');
        if ($deposit->purpose === 'membership' && $deposit->status === 'completed') {
            Session::put('membership_paid', optional(\App\MembershipPayment::find($deposit->purpose_id))->membership_id);
            $redirect = route('membership.paid');
        }
        if ($deposit->purpose === 'sale' && $deposit->status === 'completed') {
            $redirect = url('sales/gen_invoice/'.$deposit->purpose_id);
        }
        if ($deposit->purpose === 'order' && $deposit->status === 'completed') {
            $redirect = url('/menu');
        }

        return response()->json([
            'status' => $deposit->status,
            'raw' => $deposit->raw_status,
            'message' => $this->statusMessage($deposit),
            'redirect' => $redirect,
        ]);
    }

    protected function statusMessage($deposit)
    {
        if ($deposit->status === 'completed') {
            return 'Payment received. Thank you.';
        }
        if ($deposit->status === 'failed') {
            return 'Payment was not approved. You can try again.';
        }

        return 'Approve the request on your phone (dial *182*7# on MTN if needed).';
    }
}
