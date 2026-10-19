<?php

namespace App\Http\Controllers;

use App\Support\MomoUssd;

class MomoPayController extends Controller
{
    public function show($purpose, $id, $amount, $token)
    {
        if (! MomoUssd::isValid($purpose, $id, $amount, $token)) {
            abort(404);
        }

        return view('beyond.payments.momo_ussd', [
            'purpose' => $purpose,
            'reference' => $this->reference($purpose, $id),
            'amount' => MomoUssd::amount($amount),
            'code' => MomoUssd::code($amount),
            'tel' => MomoUssd::telUri($amount),
            'qr' => MomoUssd::qrDataUri($amount),
            'backUrl' => request()->query('back'),
        ]);
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
