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
    public function createFromSale(Sale $sale, $notes = '', array $itemNotes = [], $startCleaning = false)
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
                'status' => $startCleaning ? LaundryOrderItem::STATUS_CLEANING : LaundryOrderItem::STATUS_RECEIVED,
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
                'status' => LaundryOrderItem::STATUS_RECEIVED,
            ]);
        }

        $this->notify($order->fresh(['customer', 'items', 'sale']), 'received');

        return $order;
    }

    public function acceptForCleaning(LaundryOrder $order, $collectAt, array $itemCollectAts = [], array $itemIds = [])
    {
        $order->loadMissing('items');
        $ids = array_values(array_filter(array_map('intval', $itemIds)));
        if (! $ids) {
            throw new \InvalidArgumentException('Select at least one dress that is still awaiting acceptance.');
        }
        $targets = $order->items->filter(function ($item) use ($ids) {
            $status = $item->status ?: LaundryOrderItem::STATUS_RECEIVED;

            return $status === LaundryOrderItem::STATUS_RECEIVED && in_array((int) $item->id, $ids, true);
        });
        if ($targets->isEmpty()) {
            throw new \InvalidArgumentException('Select at least one dress that is still awaiting acceptance.');
        }
        $when = $this->parseCollectAt($collectAt);
        if (! $when) {
            throw new \InvalidArgumentException('Set the collection date and time.');
        }
        $userId = Auth::id();
        foreach ($targets as $item) {
            $itemWhen = $this->parseCollectAt($itemCollectAts[$item->id] ?? $collectAt) ?: $when;
            $item->collect_at = $itemWhen;
            $item->status = LaundryOrderItem::STATUS_CLEANING;
            $item->accepted_at = now();
            $item->accepted_by = $userId;
            $item->save();
        }
        $hours = max(1, (int) ceil(($when->getTimestamp() - time()) / 3600));
        $order->collect_at = $when;
        $order->cleaning_duration_hours = $hours;
        if (! $order->cleaning_started_at) {
            $order->cleaning_started_at = now();
        }
        $this->syncOrderStatus($order);
        $this->notify($order->fresh(['customer', 'items', 'sale']), 'cleaning');

        return $order;
    }

    public function startCleaning(LaundryOrder $order, $hours)
    {
        $when = now()->addHours(max(1, (int) $hours));

        return $this->acceptForCleaning($order, $when->format('Y-m-d H:i:s'));
    }

    protected function parseCollectAt($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return \Carbon\Carbon::instance($value);
        }
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        try {
            return \Carbon\Carbon::parse(str_replace('T', ' ', $value));
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function markReady(LaundryOrder $order, array $itemIds = [])
    {
        return $this->markItemsReady($itemIds, $order->id);
    }

    public function markItemsReady(array $itemIds, $orderId = null)
    {
        $ids = array_values(array_filter(array_map('intval', $itemIds)));
        if (! $ids && ! $orderId) {
            throw new \InvalidArgumentException('Select at least one dress that has been cleaned.');
        }
        $query = LaundryOrderItem::query()->where('status', LaundryOrderItem::STATUS_CLEANING);
        if ($ids) {
            $query->whereIn('id', $ids);
        } elseif ($orderId) {
            throw new \InvalidArgumentException('Select the dresses that have been cleaned.');
        }
        $items = $query->get();
        if ($items->isEmpty()) {
            throw new \InvalidArgumentException('Those dresses are not awaiting cleaning.');
        }
        $userId = Auth::id();
        $orderIds = [];
        foreach ($items as $item) {
            $item->status = LaundryOrderItem::STATUS_READY;
            $item->cleaned_at = now();
            $item->cleaned_by = $userId;
            $item->save();
            $orderIds[$item->laundry_order_id] = true;
        }
        $notified = [];
        foreach (array_keys($orderIds) as $oid) {
            $order = LaundryOrder::with(['customer', 'items', 'sale'])->find($oid);
            if (! $order) {
                continue;
            }
            if (! $order->ready_at) {
                $order->ready_at = now();
            }
            $this->syncOrderStatus($order);
            $fresh = $order->fresh(['customer', 'items.cleanedBy', 'sale']);
            $this->notify($fresh, 'ready');
            $notified[] = $fresh;
        }

        return $notified;
    }

    public function ordersForStage($status)
    {
        $query = LaundryOrder::with(['customer', 'items.cleanedBy', 'items.acceptedBy', 'sale'])
            ->whereHas('items', function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->orderBy('customer_id')
            ->orderByDesc('id');
        if ($status === LaundryOrderItem::STATUS_READY) {
            $query->whereNotIn('status', [LaundryOrder::STATUS_AWAITING_SIGNATURE, LaundryOrder::STATUS_COLLECTED]);
        } elseif ($status === LaundryOrderItem::STATUS_CLEANING) {
            $query->whereNotIn('status', [LaundryOrder::STATUS_AWAITING_SIGNATURE, LaundryOrder::STATUS_COLLECTED]);
        }

        return $query;
    }

    public function stageCounts()
    {
        $empty = [
            'received' => ['dresses' => 0, 'clients' => 0],
            'cleaning' => ['dresses' => 0, 'clients' => 0],
            'ready' => ['dresses' => 0, 'clients' => 0],
            'collect' => ['dresses' => 0, 'clients' => 0],
        ];
        if (! \Illuminate\Support\Facades\Schema::hasTable('laundry_order_items')) {
            return $empty;
        }
        $rows = LaundryOrderItem::query()
            ->selectRaw('laundry_order_items.status as status, SUM(laundry_order_items.qty) as dresses, COUNT(DISTINCT laundry_orders.customer_id) as clients')
            ->join('laundry_orders', 'laundry_orders.id', '=', 'laundry_order_items.laundry_order_id')
            ->whereNotIn('laundry_orders.status', [LaundryOrder::STATUS_AWAITING_SIGNATURE, LaundryOrder::STATUS_COLLECTED])
            ->whereIn('laundry_order_items.status', [
                LaundryOrderItem::STATUS_RECEIVED,
                LaundryOrderItem::STATUS_CLEANING,
                LaundryOrderItem::STATUS_READY,
            ])
            ->groupBy('laundry_order_items.status')
            ->get();
        foreach ($rows as $row) {
            $key = $row->status;
            if (isset($empty[$key])) {
                $empty[$key] = [
                    'dresses' => (int) $row->dresses,
                    'clients' => (int) $row->clients,
                ];
            }
        }
        $collect = LaundryOrder::query()
            ->where('status', LaundryOrder::STATUS_AWAITING_SIGNATURE)
            ->selectRaw('COUNT(DISTINCT customer_id) as clients')
            ->first();
        $collectDresses = LaundryOrderItem::query()
            ->join('laundry_orders', 'laundry_orders.id', '=', 'laundry_order_items.laundry_order_id')
            ->where('laundry_orders.status', LaundryOrder::STATUS_AWAITING_SIGNATURE)
            ->sum('laundry_order_items.qty');
        $empty['collect'] = [
            'dresses' => (int) $collectDresses,
            'clients' => (int) optional($collect)->clients,
        ];

        return $empty;
    }

    public function syncOrderStatus(LaundryOrder $order)
    {
        $order->load('items');
        if (in_array($order->status, [LaundryOrder::STATUS_AWAITING_SIGNATURE, LaundryOrder::STATUS_COLLECTED], true)) {
            $order->save();

            return $order;
        }
        $hasReceived = false;
        $hasCleaning = false;
        $hasReady = false;
        foreach ($order->items as $item) {
            $status = $item->status ?: LaundryOrderItem::STATUS_RECEIVED;
            if ($status === LaundryOrderItem::STATUS_RECEIVED) {
                $hasReceived = true;
            } elseif ($status === LaundryOrderItem::STATUS_CLEANING) {
                $hasCleaning = true;
            } elseif ($status === LaundryOrderItem::STATUS_READY) {
                $hasReady = true;
            }
        }
        if ($hasReceived) {
            $order->status = LaundryOrder::STATUS_RECEIVED;
        } elseif ($hasCleaning) {
            $order->status = LaundryOrder::STATUS_CLEANING;
        } elseif ($hasReady) {
            $order->status = LaundryOrder::STATUS_READY;
        }
        $order->save();

        return $order;
    }

    public function sendCollectLink(LaundryOrder $order)
    {
        $order->loadMissing('items');
        $stillOpen = $order->items->first(function ($item) {
            $status = $item->status ?: LaundryOrderItem::STATUS_RECEIVED;

            return in_array($status, [LaundryOrderItem::STATUS_RECEIVED, LaundryOrderItem::STATUS_CLEANING], true);
        });
        if ($stillOpen) {
            throw new \RuntimeException('Some dresses are still awaiting cleaning. Mark them cleaned first.');
        }
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
        LaundryOrderItem::where('laundry_order_id', $order->id)->update(['status' => LaundryOrderItem::STATUS_COLLECTED]);
        $this->notify($order->fresh(['customer', 'items', 'sale']), 'signed');

        return $order;
    }

    public function addCashPayment(LaundryOrder $order, $amount, $note = '')
    {
        return $this->addPayment($order, $amount, 'Cash', $note);
    }

    public function addPayment(LaundryOrder $order, $amount, $method = 'Cash', $note = '')
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
        $method = $method === 'MoMo' ? 'Momo/Orange' : ($method ?: 'Cash');
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
        $payment->paying_method = $method;
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
