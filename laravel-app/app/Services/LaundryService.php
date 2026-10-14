<?php

namespace App\Services;

use App\Account;
use App\Biller;
use App\CashRegister;
use App\Customer;
use App\LaundryOrder;
use App\LaundryOrderItem;
use App\Payment;
use App\PosSetting;
use App\Product;
use App\Product_Sale;
use App\Sale;
use App\Support\LaundryCatalog;
use App\Support\WhatsAppMessage;
use App\Warehouse;
use App\Services\Messaging\NotificationRouter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LaundryService
{
    public function createFromSale(Sale $sale, $notes = '', array $itemNotes = [], $startCleaning = true)
    {
        if ((int) $sale->sale_status === 3) {
            return null;
        }
        if (LaundryOrder::where('sale_id', $sale->id)->exists()) {
            return LaundryOrder::where('sale_id', $sale->id)->first();
        }

        $lines = Product_Sale::where('sale_id', $sale->id)->get();
        $laundryLines = [];
        foreach ($lines as $line) {
            $product = Product::find($line->product_id);
            if ($product && LaundryCatalog::isLaundryProduct($product)) {
                $laundryLines[] = [$line, $product];
            }
        }
        if (! $laundryLines) {
            return null;
        }

        $status = $startCleaning ? LaundryOrder::STATUS_CLEANING : LaundryOrder::STATUS_RECEIVED;
        $order = $this->newOrder($sale, $sale->customer_id, $notes, $status);
        foreach ($laundryLines as $index => $pair) {
            /** @var Product_Sale $line */
            list($line, $product) = $pair;
            LaundryOrderItem::create([
                'laundry_order_id' => $order->id,
                'product_id' => $product->id,
                'product_sale_id' => $line->id,
                'name' => $product->name,
                'qty' => $line->qty,
                'unit_price' => $line->net_unit_price,
                'total' => $line->total,
                'note' => $itemNotes[$index] ?? ($itemNotes[$line->product_id] ?? null),
            ]);
        }

        $this->notify($order->fresh(['customer', 'items', 'sale']), $startCleaning ? 'cleaning' : 'received');

        return $order;
    }

    public function createFromIntake(array $payload)
    {
        $customer = Customer::findOrFail($payload['customer_id']);
        $rows = $payload['items'] ?? [];
        if (! is_array($rows) || ! $rows) {
            throw new \InvalidArgumentException('Add at least one garment.');
        }

        $pos = PosSetting::latest()->first();
        $warehouse = Warehouse::where('is_active', true)->first();
        $biller = Biller::where('is_active', true)->first();
        if ($pos) {
            $warehouse = Warehouse::find($pos->warehouse_id) ?: $warehouse;
            $biller = Biller::find($pos->biller_id) ?: $biller;
        }
        if (! $warehouse || ! $biller) {
            throw new \RuntimeException('Set a default warehouse and biller in POS settings before taking laundry.');
        }

        $item = 0;
        $totalQty = 0;
        $totalPrice = 0;
        $built = [];
        foreach ($rows as $row) {
            $product = Product::where('id', $row['product_id'] ?? 0)->where('is_active', 1)->first();
            if (! $product || ! LaundryCatalog::isLaundryProduct($product)) {
                continue;
            }
            $qty = max(1, (float) ($row['qty'] ?? 1));
            $list = (float) $product->price;
            $unit = $this->memberUnitPrice($customer->id, $product);
            $lineTotal = round($unit * $qty, 2);
            $built[] = [
                'product' => $product,
                'qty' => $qty,
                'unit_price' => $unit,
                'list_price' => $list,
                'total' => $lineTotal,
                'note' => trim((string) ($row['note'] ?? '')),
            ];
            $item++;
            $totalQty += $qty;
            $totalPrice += $lineTotal;
        }
        if (! $built) {
            throw new \InvalidArgumentException('Add at least one laundry product.');
        }

        $payNow = ! empty($payload['pay_now']);
        $paidAmount = $payNow ? $totalPrice : 0;
        $paymentStatus = $paidAmount >= $totalPrice && $totalPrice > 0 ? 4 : ($paidAmount > 0 ? 3 : 2);

        $cashRegister = CashRegister::where([
            ['user_id', Auth::id()],
            ['warehouse_id', $warehouse->id],
            ['status', true],
        ])->first();

        $sale = Sale::create([
            'reference_no' => 'lnd-'.date('Ymd').'-'.date('His'),
            'user_id' => Auth::id(),
            'cash_register_id' => $cashRegister ? $cashRegister->id : null,
            'customer_id' => $customer->id,
            'customer_group_id' => $customer->customer_group_id,
            'warehouse_id' => $warehouse->id,
            'biller_id' => $biller->id,
            'item' => $item,
            'total_qty' => $totalQty,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => $totalPrice,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
            'shipping_cost' => 0,
            'grand_total' => $totalPrice,
            'sale_status' => 1,
            'payment_status' => $paymentStatus,
            'paid_amount' => $paidAmount,
            'sale_note' => $payload['notes'] ?? null,
        ]);

        $productSaleIds = [];
        foreach ($built as $row) {
            $ps = Product_Sale::create([
                'sale_id' => $sale->id,
                'product_id' => $row['product']->id,
                'category_id' => $row['product']->category_id,
                'warehouse_id' => $warehouse->id,
                'qty' => $row['qty'],
                'sale_unit_id' => 0,
                'net_unit_price' => $row['unit_price'],
                'discount' => max(0, $row['list_price'] - $row['unit_price']) * $row['qty'],
                'tax_rate' => 0,
                'tax' => 0,
                'total' => $row['total'],
            ]);
            $productSaleIds[] = [$ps, $row];
        }

        if ($payNow && $paidAmount > 0) {
            $account = Account::where('is_active', 1)->orderByDesc('is_default')->first();
            $payment = new Payment();
            $payment->user_id = Auth::id();
            $payment->sale_id = $sale->id;
            if ($cashRegister) {
                $payment->cash_register_id = $cashRegister->id;
            }
            $payment->account_id = $account ? $account->id : null;
            $payment->payment_reference = 'lndp-'.date('Ymd').'-'.date('His');
            $payment->amount = $paidAmount;
            $payment->change = 0;
            $payment->paying_method = 'Cash';
            $payment->payment_note = 'Laundry drop-off';
            $payment->save();
        }

        $order = $this->newOrder($sale, $customer->id, $payload['notes'] ?? '');
        foreach ($productSaleIds as $pair) {
            list($ps, $row) = $pair;
            LaundryOrderItem::create([
                'laundry_order_id' => $order->id,
                'product_id' => $row['product']->id,
                'product_sale_id' => $ps->id,
                'name' => $row['product']->name,
                'qty' => $row['qty'],
                'unit_price' => $row['unit_price'],
                'total' => $row['total'],
                'note' => $row['note'] !== '' ? $row['note'] : null,
            ]);
        }

        $this->notify($order->fresh(['customer', 'items', 'sale']), 'received');

        return $order;
    }

    public function startCleaning(LaundryOrder $order, $hours)
    {
        if ($order->status !== LaundryOrder::STATUS_RECEIVED) {
            throw new \RuntimeException('Only received tickets can be sent to cleaning.');
        }
        $hours = max(1, (int) $hours);
        $order->status = LaundryOrder::STATUS_CLEANING;
        $order->cleaning_duration_hours = $hours;
        $order->cleaning_started_at = now();
        $order->save();
        $this->notify($order->fresh(['customer', 'items', 'sale']), 'cleaning');

        return $order;
    }

    public function markReady(LaundryOrder $order)
    {
        if ($order->status !== LaundryOrder::STATUS_CLEANING) {
            throw new \RuntimeException('Only cleaning tickets can be marked ready.');
        }
        $order->status = LaundryOrder::STATUS_READY;
        $order->ready_at = now();
        $order->save();
        $this->notify($order->fresh(['customer', 'items', 'sale']), 'ready');

        return $order;
    }

    public function sendCollectLink(LaundryOrder $order)
    {
        if (! in_array($order->status, [LaundryOrder::STATUS_READY, LaundryOrder::STATUS_AWAITING_SIGNATURE], true)) {
            throw new \RuntimeException('Ticket is not ready for collection.');
        }
        if (! $order->isPaid()) {
            throw new \RuntimeException('Clothes cannot be collected until the bill is paid.');
        }
        $order->rotateCollectToken();
        $order->status = LaundryOrder::STATUS_AWAITING_SIGNATURE;
        $order->collect_sent_at = now();
        $order->save();
        $this->notify($order->fresh(['customer', 'items', 'sale']), 'collect');

        return $order;
    }

    public function completeCollection(LaundryOrder $order, $collectorName, $signaturePath)
    {
        $order->status = LaundryOrder::STATUS_COLLECTED;
        $order->collector_name = $collectorName;
        $order->signature_path = $signaturePath;
        $order->signed_at = now();
        $order->collected_at = now();
        $order->collect_token = null;
        $order->save();
        $this->notify($order->fresh(['customer', 'items', 'sale']), 'signed');

        return $order;
    }

    public function addCashPayment(LaundryOrder $order, $amount, $note = '')
    {
        $sale = $order->sale;
        if (! $sale) {
            throw new \RuntimeException('This ticket has no sale to pay.');
        }
        $amount = round((float) $amount, 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Enter a payment amount.');
        }
        $due = max(0, (float) $sale->grand_total - (float) $sale->paid_amount);
        if ($amount > $due) {
            $amount = $due;
        }
        $sale->paid_amount = (float) $sale->paid_amount + $amount;
        $balance = (float) $sale->grand_total - (float) $sale->paid_amount;
        $sale->payment_status = $balance <= 0 ? 4 : ($sale->paid_amount > 0 ? 3 : 2);
        $sale->save();

        $account = Account::where('is_active', 1)->orderByDesc('is_default')->first();
        $cashRegister = CashRegister::where([
            ['user_id', Auth::id()],
            ['warehouse_id', $sale->warehouse_id],
            ['status', true],
        ])->first();
        $payment = new Payment();
        $payment->user_id = Auth::id();
        $payment->sale_id = $sale->id;
        if ($cashRegister) {
            $payment->cash_register_id = $cashRegister->id;
        }
        $payment->account_id = $account ? $account->id : null;
        $payment->payment_reference = 'lndp-'.date('Ymd').'-'.date('His');
        $payment->amount = $amount;
        $payment->change = 0;
        $payment->paying_method = 'Cash';
        $payment->payment_note = $note !== '' ? $note : 'Laundry collection payment';
        $payment->save();

        return $sale->fresh();
    }

    public function memberUnitPrice($customerId, Product $product)
    {
        $list = (float) $product->price;
        try {
            $payload = app(MembershipService::class)->posPayloadForCustomer($customerId);
        } catch (\Throwable $e) {
            return $list;
        }
        $percent = (float) ($payload['percentage'] ?? 0);
        $mode = $payload['discount_mode'] ?? 'markup';
        if ($percent <= 0) {
            return $list;
        }
        if ($mode === 'discount') {
            return round($list * (1 - ($percent / 100)), 2);
        }

        return round($list * (1 + ($percent / 100)), 2);
    }

    public function laundryProducts()
    {
        return LaundryCatalog::queryProducts()->get();
    }

    public function notify(LaundryOrder $order, $kind)
    {
        $customer = $order->customer;
        if (! $customer || empty($customer->phone_number)) {
            return;
        }
        $msg = WhatsAppMessage::forRecipient($customer, function () use ($order, $kind, $customer) {
            return WhatsAppMessage::laundryStatus($customer->name, $order, $kind);
        });
        try {
            app(NotificationRouter::class)->sendWhatsAppText($customer->phone_number, $msg);
        } catch (\Throwable $e) {
            Log::warning('Laundry WhatsApp failed: '.$e->getMessage(), ['order' => $order->id, 'kind' => $kind]);
        }
    }

    protected function newOrder(Sale $sale, $customerId, $notes = '', $status = null)
    {
        $status = $status ?: LaundryOrder::STATUS_RECEIVED;

        return LaundryOrder::create([
            'reference_no' => $this->nextReference(),
            'sale_id' => $sale->id,
            'customer_id' => $customerId,
            'user_id' => Auth::id(),
            'status' => $status,
            'notes' => $notes !== '' ? $notes : null,
            'qr_token' => Str::random(40),
            'cleaning_started_at' => $status === LaundryOrder::STATUS_CLEANING ? now() : null,
        ]);
    }

    protected function nextReference()
    {
        $n = (int) LaundryOrder::max('id') + 1;

        return 'LND-'.str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    }
}
