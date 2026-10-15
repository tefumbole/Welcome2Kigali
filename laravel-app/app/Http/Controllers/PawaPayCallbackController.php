<?php

namespace App\Http\Controllers;

use App\MembershipPayment;
use App\PawaPayDeposit;
use App\Services\PawaPayPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class PawaPayCallbackController extends Controller
{
    public function handle(Request $request, PawaPayPaymentService $payments)
    {
        $payload = [];
        try {
            $json = $request->json();
            if ($json) {
                $payload = $json->all();
            }
        } catch (\Throwable $e) {
            $payload = [];
        }
        if (! is_array($payload) || empty($payload)) {
            $payload = $request->all();
        }
        if (empty($payload) && $request->getContent()) {
            $decoded = json_decode($request->getContent(), true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }
        Log::info('PawaPay callback', [
            'deposit_id' => $payload['depositId'] ?? ($payload['data']['depositId'] ?? null),
            'status' => $payload['status'] ?? ($payload['data']['status'] ?? null),
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

        $confirmed = $deposit->status === 'completed' && $deposit->applied_at;
        $uiStatus = $confirmed ? 'completed' : ($deposit->status === 'failed' ? 'failed' : 'pending');

        return response()->json([
            'status' => $uiStatus,
            'raw' => $deposit->raw_status,
            'message' => $this->statusMessage($deposit, $confirmed),
            'redirect' => $confirmed ? $this->completedRedirect($deposit) : null,
            'clear_pos' => $confirmed && $deposit->purpose === 'sale',
        ]);
    }

    protected function completedRedirect(PawaPayDeposit $deposit)
    {
        if ($deposit->purpose === 'membership') {
            Session::put('membership_paid', optional(MembershipPayment::find($deposit->purpose_id))->membership_id);

            return route('membership.paid');
        }
        if ($deposit->purpose === 'sale') {
            return url('sales/gen_invoice/'.$deposit->purpose_id).'?autoprint=1';
        }
        if ($deposit->purpose === 'order') {
            return url('/menu');
        }

        return $deposit->redirect_url ?: url('/');
    }

    protected function statusMessage(PawaPayDeposit $deposit, $confirmed)
    {
        if ($confirmed) {
            return 'Payment received. Opening the invoice.';
        }
        if ($deposit->status === 'failed') {
            return 'Payment was not approved. No money was taken. You can try again.';
        }

        return 'Waiting for the customer to approve on the phone. This stays pending until PawaPay confirms the money.';
    }
}
