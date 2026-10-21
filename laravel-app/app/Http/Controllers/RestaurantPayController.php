<?php

namespace App\Http\Controllers;

use App\Sale;
use App\Services\Restaurant\RestaurantPayments;
use App\Support\MomoUssd;
use App\Support\SalePayQr;
use Illuminate\Http\Request;

class RestaurantPayController extends Controller
{
    public function show($sale, $token)
    {
        $sale = $this->sale($sale, $token);
        $paid = (float) $sale->paid_amount;
        $due = max(0, (float) $sale->grand_total - $paid);

        return view('restaurant.pay', [
            'sale' => $sale,
            'due' => $due,
            'paid' => (int) $sale->payment_status === 4 || $due <= 0,
        ]);
    }

    public function start(Request $request, $sale, $token)
    {
        $sale = $this->sale($sale, $token);
        if ((int) $sale->payment_status === 4) {
            return redirect()->route('restaurant.pay', [$sale->id, SalePayQr::token($sale)])
                ->with('message', 'This invoice is already paid.');
        }
        $due = max(0, (float) $sale->grand_total - (float) $sale->paid_amount);
        if ($due <= 0) {
            return back()->with('message', 'This invoice is already paid.');
        }

        return redirect()->to(MomoUssd::payUrl('sale', $sale->id, $due));
    }

    public function callback(Request $request)
    {
        $status = $request->input('status');
        $saleId = $request->input('external_reference');
        $sale = Sale::find($saleId);
        if (! $sale) {
            return redirect('/')->with('not_permitted', 'Invoice not found.');
        }
        $token = SalePayQr::token($sale);
        $back = redirect()->route('restaurant.pay', [$sale->id, $token]);
        if ($status !== 'SUCCESSFUL') {
            return $back->with('not_permitted', 'Payment was not completed.');
        }
        $reference = $request->input('reference');
        $amount = $request->input('app_amount', $request->input('amount'));
        $result = RestaurantPayments::recordGateway($sale, $amount, $reference, null);
        if ($result === 'duplicate' || $result === 'paid') {
            return $back->with('message', 'This payment was already recorded.');
        }
        if ($result !== 'recorded') {
            return $back->with('not_permitted', 'Payment could not be recorded.');
        }

        return $back->with('message', 'Payment received. Thank you.');
    }

    protected function sale($id, $token)
    {
        $sale = Sale::with('customer')->findOrFail($id);
        if (! SalePayQr::isValid($sale, $token)) {
            abort(404);
        }

        return $sale;
    }
}
