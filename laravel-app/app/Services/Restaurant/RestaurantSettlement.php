<?php

namespace App\Services\Restaurant;

use App\Product_Sale;
use App\RestaurantOrder;
use App\Sale;
use Illuminate\Support\Collection;

class RestaurantSettlement
{
    public static function attach(Collection $sales)
    {
        $ids = $sales->pluck('id')->filter()->all();
        $orders = $ids
            ? RestaurantOrder::with('items')->whereIn('sale_id', $ids)->get()->keyBy('sale_id')
            : collect();
        $lines = $ids
            ? Product_Sale::whereIn('sale_id', $ids)->get()->groupBy('sale_id')
            : collect();
        foreach ($sales as $sale) {
            $sale->settlement = self::compute(
                $sale,
                $orders->get($sale->id),
                $lines->get($sale->id) ?: collect()
            );
        }

        return $sales;
    }

    public static function compute(Sale $sale, $order, $lines)
    {
        $paid = (float) $sale->paid_amount;
        $grand = (float) $sale->grand_total;
        $plainDue = max(0, round($grand - $paid, 2));
        if (! $order || ! $order->items || $order->items->isEmpty()) {
            return [
                'queued' => false,
                'pending_delivery' => false,
                'billable' => $grand,
                'due' => $plainDue,
                'badge' => ((int) $sale->payment_status === 1) ? 'Pending' : ($plainDue <= 0.009 ? 'Paid' : 'Due'),
            ];
        }

        $queuedIds = [];
        $deliveredIds = [];
        $pending = false;
        foreach ($order->items as $item) {
            if ($item->status !== 'delivered') {
                $pending = true;
            }
            if (! $item->product_sale_id) {
                continue;
            }
            $queuedIds[(int) $item->product_sale_id] = true;
            if (! $item->remake_of_id && $item->status === 'delivered') {
                $deliveredIds[(int) $item->product_sale_id] = true;
            }
        }

        $lineTotal = 0;
        $openTotal = 0;
        foreach ($lines as $line) {
            $amount = (float) $line->total;
            $lineTotal += $amount;
            $id = (int) $line->id;
            if (! isset($queuedIds[$id]) || isset($deliveredIds[$id])) {
                $openTotal += $amount;
            }
        }

        $billable = $openTotal;
        if ($lineTotal > 0.009 && abs($grand - $lineTotal) > 0.009) {
            $billable = $grand * ($openTotal / $lineTotal);
        }
        $billable = round($billable, 2);
        $due = max(0, round($billable - $paid, 2));
        if ($pending) {
            $badge = 'Pending delivery';
        } elseif ($due <= 0.009) {
            $badge = 'Paid';
        } else {
            $badge = 'Due';
        }

        return [
            'queued' => true,
            'pending_delivery' => $pending,
            'billable' => $billable,
            'due' => $due,
            'badge' => $badge,
        ];
    }
}
