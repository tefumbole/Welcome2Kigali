<?php

namespace App\Http\Controllers;

use App\Product_Sale;
use App\RestaurantOrder;
use App\Sale;

class RestaurantMenuController extends Controller
{
    public function redirectToPos()
    {
        return redirect('/pos');
    }

    public function confirmation($sale, $token)
    {
        $sale = Sale::with('customer')->findOrFail($sale);
        $order = RestaurantOrder::with(['items', 'diningTable'])->where('sale_id', $sale->id)->firstOrFail();
        if (! hash_equals($order->token, (string) $token)) {
            abort(404);
        }
        $lines = Product_Sale::where('sale_id', $sale->id)->get();

        return view('restaurant.confirmation', [
            'sale' => $sale,
            'order' => $order,
            'lines' => $lines,
            'staff' => collect(),
            'public' => true,
        ]);
    }
}
