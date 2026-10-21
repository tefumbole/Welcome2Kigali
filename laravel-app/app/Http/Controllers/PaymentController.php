<?php

namespace App\Http\Controllers;

use App\Account;
use App\Deposit;
use App\RestaurantOrder;
use App\Sale;
use App\Services\Restaurant\RestaurantSettlement;
use Illuminate\Support\Facades\Schema;

class PaymentController extends Controller
{
    public function index()
    {
        $sales = $this->awaitingSales();
        $lims_account_list = Account::orderByDesc('id')->get();
        return view('payment.customer-data', compact('sales', 'lims_account_list'));
    }


    public function AwaitingPayments($id)
    {
        $sales = $this->awaitingSales($id);
        $lims_account_list = Account::orderByDesc('id')->get();
        return view('payment.customer-data', compact('sales', 'lims_account_list'));
    }

    protected function awaitingSales($customerId = null)
    {
        $query = Sale::with('customer')->orderByDesc('id');
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
        $open = (clone $query)->where('payment_status', '!=', 4)->get();
        $pendingPaid = collect();
        if (Schema::hasTable('restaurant_orders') && Schema::hasTable('restaurant_order_items')) {
            $ids = RestaurantOrder::whereHas('items', function ($items) {
                $items->where('status', '!=', 'delivered');
            })->pluck('sale_id');
            if ($ids->isNotEmpty()) {
                $paid = Sale::with('customer')->where('payment_status', 4)->whereIn('id', $ids);
                if ($customerId) {
                    $paid->where('customer_id', $customerId);
                }
                $pendingPaid = $paid->orderByDesc('id')->get();
            }
            $sales = $open->merge($pendingPaid)->unique('id')->sortByDesc('id')->values();
            RestaurantSettlement::attach($sales);

            return $sales;
        }

        return $open;
    }

    public function Desposit() {
        $deposits = Deposit::orderByDesc('id')->get();
        return view('payment.deposits', compact('deposits'));
    }
}
