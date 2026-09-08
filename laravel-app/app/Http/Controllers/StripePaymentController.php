<?php

namespace App\Http\Controllers;

use App\Services\StripePaymentService;
use App\StripeCheckout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class StripePaymentController extends Controller
{
    public function webhook(Request $request, StripePaymentService $payments)
    {
        $payments->handleWebhook($request->getContent(), $request->header('Stripe-Signature'));

        return response()->json(['status' => 'ok'], 200);
    }

    public function success(Request $request, StripePaymentService $payments)
    {
        $sessionId = (string) $request->query('session_id');
        if ($sessionId === '') {
            return redirect('/')->with('not_permitted', 'Missing card payment session.');
        }

        $row = $payments->completeFromSessionId($sessionId);
        if (! $row) {
            return redirect('/')->with('not_permitted', 'Card payment was not found.');
        }

        if ($row->status !== 'completed') {
            return redirect($this->fallbackUrl($row))->with('not_permitted', 'Card payment was not completed.');
        }

        return redirect($this->successUrl($row));
    }

    public function cancel(Request $request, StripePaymentService $payments)
    {
        $sessionId = (string) $request->query('session_id');
        $row = $sessionId !== '' ? $payments->markCanceled($sessionId) : null;
        $url = $row ? $this->fallbackUrl($row) : url('/');

        return redirect($url)->with('not_permitted', 'Visa payment was cancelled.');
    }

    protected function successUrl(StripeCheckout $row)
    {
        if ($row->purpose === 'membership') {
            Session::put('membership_paid', optional(\App\MembershipPayment::find($row->purpose_id))->membership_id);

            return route('membership.paid');
        }
        if ($row->purpose === 'sale') {
            return url('sales/gen_invoice/'.$row->purpose_id);
        }
        if ($row->purpose === 'order') {
            return url('/menu');
        }

        return $row->redirect_url ?: url('/');
    }

    protected function fallbackUrl(StripeCheckout $row)
    {
        if ($row->redirect_url) {
            return $row->redirect_url;
        }
        if ($row->purpose === 'sale') {
            return url('/pos');
        }
        if ($row->purpose === 'order') {
            return url('/checkout');
        }

        return url('/');
    }
}
