<?php

namespace App\Services\Restaurant;

use App\Biller;
use App\Customer;
use App\CustomerGroup;
use App\MealRejection;
use App\OrderReview;
use App\Product;
use App\Product_Sale;
use App\Product_Warehouse;
use App\RestaurantOrder;
use App\RestaurantOrderEvent;
use App\RestaurantOrderItem;
use App\RestaurantTable;
use App\Sale;
use App\Unit;
use App\User;
use App\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class RestaurantOrderService
{
    public function attachFromRequest(Sale $sale, Request $request)
    {
        if (! Schema::hasTable('restaurant_orders')) {
            return null;
        }
        $existing = RestaurantOrder::where('sale_id', $sale->id)->first();
        if ($existing) {
            return $existing;
        }

        $lines = Product_Sale::where('sale_id', $sale->id)->orderBy('id')->get();
        $online = $request->input('order_source') === 'frontend';
        $routed = [];
        foreach ($lines as $index => $line) {
            $categoryId = $line->category_id;
            if (! $categoryId && $line->product_id) {
                $product = Product::find($line->product_id);
                $categoryId = $product ? $product->category_id : null;
            }
            $lane = RestaurantDepartment::laneForCategoryId($categoryId);
            $department = RestaurantDepartment::forCategoryId($categoryId);
            if (! $lane && $department) {
                $lane = $department === 'food' ? 'restaurant' : 'bar';
            }
            if (! $department && $lane) {
                $department = $lane === 'restaurant' ? 'food' : 'drinks';
            }
            if (! $lane || ! $department) {
                continue;
            }
            if ($online) {
                $lane = 'online';
            }
            $routed[] = ['line' => $line, 'department' => $department, 'lane' => $lane, 'index' => $index];
        }

        if (! $routed) {
            return null;
        }

        $service = $request->input('service_type');
        $waitressId = $this->staffId($request->input('waitress_user_id'));
        $comments = (array) $request->input('item_comment', []);

        $order = RestaurantOrder::create([
            'sale_id' => $sale->id,
            'source' => $request->input('order_source') === 'frontend' ? 'frontend' : 'pos',
            'service_type' => $service === 'dine_in' ? 'dine_in' : 'take_away',
            'restaurant_table_id' => $this->tableId($request->input('restaurant_table_id')),
            'placed_by_user_id' => $request->input('order_source') === 'frontend' ? null : (auth()->id() ?: null),
            'waitress_user_id' => $waitressId,
            'customer_comment' => $request->input('customer_comment') ?: $sale->sale_note,
            'internal_note' => $request->input('internal_note') ?: $sale->staff_note,
            'token' => $this->token(),
            'checkout_token' => $request->input('checkout_token') ?: null,
        ]);

        foreach ($routed as $row) {
            $line = $row['line'];
            $product = Product::find($line->product_id);
            $this->makeItem($order, [
                'product_sale_id' => $line->id,
                'product_id' => $line->product_id,
                'product_name' => $product ? $product->name : 'Item',
                'department' => $row['department'],
                'lane' => $row['lane'],
                'qty' => $line->qty,
                'customer_comment' => isset($comments[$row['index']]) ? $comments[$row['index']] : null,
            ]);
        }

        $this->event($order, null, 'placed', $order->placed_by_user_id, 'Order '.$sale->reference_no.' from '.$order->source);
        $this->notifyReceived($order);

        return $order;
    }

    public function placePublic(array $input)
    {
        $token = isset($input['checkout_token']) ? (string) $input['checkout_token'] : '';
        if ($token !== '') {
            $existing = RestaurantOrder::where('checkout_token', $token)->first();
            if ($existing) {
                return Sale::find($existing->sale_id);
            }
        }

        $items = isset($input['items']) ? $input['items'] : [];
        $lines = [];
        foreach ($items as $item) {
            $qty = isset($item['qty']) ? (float) $item['qty'] : 0;
            if ($qty <= 0 || empty($item['product_id'])) {
                continue;
            }
            $product = Product::where('is_active', true)->find($item['product_id']);
            if (! $product) {
                continue;
            }
            $department = RestaurantDepartment::forCategoryId($product->category_id);
            if (! $department) {
                continue;
            }
            $lines[] = ['product' => $product, 'qty' => $qty, 'comment' => isset($item['comment']) ? $item['comment'] : null, 'department' => $department];
        }
        if (! $lines) {
            throw new \InvalidArgumentException('Choose at least one food or drink.');
        }

        $phone = \App\Support\WhatsAppPhone::normalize($input['phone']);
        $customer = Customer::where('phone_number', $phone)->first();
        if (! $customer) {
            $group = CustomerGroup::query()->orderBy('id')->first();
            $customer = Customer::create([
                'customer_group_id' => $group ? $group->id : 1,
                'name' => $input['name'],
                'phone_number' => $phone,
                'country' => isset($input['country']) ? $input['country'] : null,
                'address' => 'Restaurant',
                'city' => 'Kigali',
                'is_active' => 1,
            ]);
        } elseif (! empty($input['name'])) {
            $customer->name = $input['name'];
            if (! empty($input['country'])) {
                $customer->country = $input['country'];
            }
            $customer->save();
        }

        $warehouse = Warehouse::query()->orderBy('id')->first();
        $biller = Biller::query()->orderBy('id')->first();
        if (! $warehouse || ! $biller) {
            throw new \RuntimeException('Warehouse or biller is not configured.');
        }

        $totalQty = 0;
        $totalPrice = 0;
        $totalTax = 0;
        foreach ($lines as $line) {
            $priced = $this->priceLine($line['product'], $line['qty']);
            $totalQty += $line['qty'];
            $totalPrice += $priced['net'];
            $totalTax += $priced['tax'];
        }
        $grand = $totalPrice + $totalTax;
        $reference = $this->reference();

        $sale = Sale::create([
            'reference_no' => $reference,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'biller_id' => $biller->id,
            'user_id' => null,
            'item' => count($lines),
            'total_qty' => $totalQty,
            'total_discount' => 0,
            'total_tax' => $totalTax,
            'total_price' => $totalPrice,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
            'shipping_cost' => 0,
            'grand_total' => $grand,
            'sale_status' => 1,
            'payment_status' => 2,
            'paid_amount' => 0,
            'paid_by_id' => 9,
            'sale_note' => isset($input['customer_comment']) ? $input['customer_comment'] : null,
        ]);

        $comments = [];
        foreach ($lines as $line) {
            $priced = $this->priceLine($line['product'], $line['qty']);
            Product_Sale::create([
                'sale_id' => $sale->id,
                'product_id' => $line['product']->id,
                'category_id' => $line['product']->category_id,
                'warehouse_id' => $warehouse->id,
                'qty' => $line['qty'],
                'sale_unit_id' => $line['product']->sale_unit_id ?: 0,
                'net_unit_price' => $priced['unit'],
                'discount' => 0,
                'tax_rate' => $priced['rate'],
                'tax' => $priced['tax'],
                'total' => $priced['net'] + $priced['tax'],
            ]);
            $this->deductStock($line['product'], $line['qty'], $warehouse->id);
            $comments[] = $line['comment'];
        }

        $fake = Request::create('/', 'POST', [
            'service_type' => (isset($input['service_type']) && $input['service_type'] === 'dine_in') ? 'dine_in' : 'take_away',
            'restaurant_table_id' => isset($input['restaurant_table_id']) ? $input['restaurant_table_id'] : null,
            'customer_comment' => isset($input['customer_comment']) ? $input['customer_comment'] : null,
            'order_source' => 'frontend',
            'checkout_token' => $token !== '' ? $token : null,
            'item_comment' => $comments,
        ]);
        $this->attachFromRequest($sale, $fake);

        return $sale->fresh();
    }

    public function accept(RestaurantOrderItem $item, User $user, $minutes)
    {
        $this->assertStatus($item, 'awaiting', 'preparing');
        $minutes = is_numeric($minutes) ? (int) $minutes : 0;
        if ($minutes > 240) {
            throw new \InvalidArgumentException('Enter a preparation time between 1 and 240 minutes.');
        }
        $timed = $minutes >= 1;
        $item->status = 'preparing';
        $item->accepted_by = $user->id;
        $item->accepted_at = now();
        $item->estimate_minutes = $timed ? $minutes : null;
        if ($timed && ! $item->original_estimate_minutes) {
            $item->original_estimate_minutes = $minutes;
        }
        $item->expected_ready_at = $timed ? now()->addMinutes($minutes) : null;
        $item->save();
        $this->event($item->order, $item, 'accepted', $user->id, $timed ? $minutes.' min' : null);
        $text = RestaurantDepartment::label($item->lane ?: $item->department).' accepted '.$item->product_name.' (x'.$this->qty($item).').';
        if ($timed) {
            $text .= ' Ready in about '.$minutes.' minutes.';
        }
        $this->notify($item->order, $text);

        return $item;
    }

    public function reviseEstimate(RestaurantOrderItem $item, User $user, $minutes)
    {
        if (! in_array($item->status, ['preparing', 'awaiting'], true)) {
            throw new \InvalidArgumentException('The estimate can be changed while the item is still being prepared.');
        }
        $minutes = (int) $minutes;
        if ($minutes < 1 || $minutes > 240) {
            throw new \InvalidArgumentException('Enter a preparation time between 1 and 240 minutes.');
        }
        if (! $item->original_estimate_minutes) {
            $item->original_estimate_minutes = $item->estimate_minutes ?: $minutes;
        }
        $item->estimate_minutes = $minutes;
        $item->expected_ready_at = now()->addMinutes($minutes);
        $item->save();
        $this->event($item->order, $item, 'estimate_revised', $user->id, $minutes.' min');
        $this->notify($item->order, 'Updated time for '.$item->product_name.': about '.$minutes.' minutes.');

        return $item;
    }

    public function markReady(RestaurantOrderItem $item, User $user)
    {
        $this->assertStatus($item, 'preparing', 'ready');
        $item->status = 'ready';
        $item->ready_by = $user->id;
        $item->ready_at = now();
        $item->save();
        $this->event($item->order, $item, 'ready', $user->id, null);
        $this->notify($item->order, $item->product_name.' is ready for delivery.');

        return $item;
    }

    public function deliver(RestaurantOrderItem $item, User $user)
    {
        $this->assertStatus($item, 'ready', 'delivered');
        $item->status = 'delivered';
        $item->delivered_by = $user->id;
        $item->delivered_at = now();
        $item->save();
        $order = $item->order()->with('items', 'sale.customer')->first();
        $this->event($order, $item, 'delivered', $user->id, null);
        $sale = $order->sale ?: Sale::find($order->sale_id);
        $ref = $sale ? $sale->reference_no : $order->sale_id;
        $this->notify($order, $item->product_name.' has been delivered. Track: '.$order->trackUrl().' Review: '.url('/reviews?ref='.urlencode((string) $ref)));
        if ($order->isFulfilled()) {
            $this->sendReviewLink($order);
        }

        return $item;
    }

    public function reject(RestaurantOrderItem $item, User $user, $qty, $reason, $resolution)
    {
        $allowed = ['remake', 'replacement', 'cancellation', 'price_adjustment', 'refund'];
        if (! in_array($resolution, $allowed, true)) {
            throw new \InvalidArgumentException('Choose a resolution.');
        }
        $qty = (float) $qty;
        if ($qty <= 0 || $qty > (float) $item->qty) {
            throw new \InvalidArgumentException('Quantity must be between 1 and the served amount.');
        }
        $reason = trim((string) $reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('A reason is required.');
        }

        $remake = null;
        if ($resolution === 'remake' || $resolution === 'replacement') {
            $remake = $this->makeItem($item->order, [
                'product_sale_id' => $item->product_sale_id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'department' => $item->department,
                'lane' => $item->lane,
                'qty' => $qty,
                'customer_comment' => $item->customer_comment,
                'attempt_no' => ((int) $item->attempt_no) + 1,
                'remake_of_id' => $item->id,
            ]);
        }

        $rejection = MealRejection::create([
            'restaurant_order_id' => $item->restaurant_order_id,
            'restaurant_order_item_id' => $item->id,
            'qty' => $qty,
            'reason' => $reason,
            'resolution' => $resolution,
            'user_id' => $user->id,
            'remake_item_id' => $remake ? $remake->id : null,
        ]);
        if (in_array($resolution, ['cancellation', 'refund'], true) && abs($qty - (float) $item->qty) < 0.001) {
            $this->dropLineFromBill($item);
        }
        $this->event($item->order, $item, 'rejected', $user->id, $resolution.': '.$reason);

        return $rejection;
    }

    public function deny(RestaurantOrderItem $item, User $user, $reason)
    {
        if (! in_array($item->status, ['awaiting', 'preparing'], true)) {
            throw new \InvalidArgumentException('Only an order that is still open can be denied.');
        }
        $reason = trim((string) $reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('A reason is required.');
        }
        $wasAwaiting = $item->status === 'awaiting';
        $item->status = 'rejected';
        $item->internal_note = $reason;
        $item->save();
        $this->dropLineFromBill($item);
        if ($wasAwaiting) {
            $this->restoreItemStock($item);
        }
        $order = $item->order ?: RestaurantOrder::find($item->restaurant_order_id);
        $this->event($order, $item, 'denied', $user->id, $reason);
        if ($order) {
            $this->notify($order, $item->product_name.' was not accepted. '.$reason.'. This item will not be billed.');
        }

        return $item;
    }

    public function proposePrice(RestaurantOrderItem $item, User $user, $price, $note)
    {
        $this->assertOpenForChange($item);
        $this->assertChangeColumns();
        $price = round((float) $price, 2);
        if ($price < 0) {
            throw new \InvalidArgumentException('Enter a price of zero or more.');
        }
        $item->proposed_price = $price;
        $item->proposed_note = trim((string) $note) !== '' ? trim((string) $note) : null;
        $item->client_signature = null;
        $item->client_signed_at = null;
        $item->save();
        $order = $item->order ?: RestaurantOrder::find($item->restaurant_order_id);
        $this->event($order, $item, 'price_proposed', $user->id, (string) $price);
        if ($order) {
            $this->notify($order, 'The price of '.$item->product_name.' is now '.number_format($price, 0).' each. Please open the tracking link and sign to confirm. Until you sign, the original price stays.');
        }

        return $item;
    }

    public function requestModification(RestaurantOrderItem $item, User $user, $note)
    {
        $this->assertOpenForChange($item);
        $this->assertChangeColumns();
        $note = trim((string) $note);
        if ($note === '') {
            throw new \InvalidArgumentException('Describe the change the guest should confirm.');
        }
        $item->proposed_note = $note;
        $item->client_signature = null;
        $item->client_signed_at = null;
        $item->save();
        $order = $item->order ?: RestaurantOrder::find($item->restaurant_order_id);
        $this->event($order, $item, 'modification', $user->id, $note);
        if ($order) {
            $this->notify($order, 'Please confirm a change to '.$item->product_name.': '.$note.'. Open the tracking link and sign.');
        }

        return $item;
    }

    public function signChange(RestaurantOrderItem $item, $name, $signature)
    {
        $this->assertChangeColumns();
        if (! $item->awaitingSignature()) {
            throw new \InvalidArgumentException('There is nothing waiting for a signature on this item.');
        }
        $name = trim((string) $name);
        if ($name === '') {
            throw new \InvalidArgumentException('Enter the name to sign with.');
        }
        $signature = (string) $signature;
        if (! preg_match('#^data:image/png;base64,#', $signature) || strlen($signature) < 80 || strlen($signature) > 180000) {
            throw new \InvalidArgumentException('Please sign in the box before submitting.');
        }
        $item->client_signature = $signature;
        $item->client_signed_at = now();
        if ($item->internal_note) {
            $item->internal_note = trim($item->internal_note."\nSigned by ".$name);
        } else {
            $item->internal_note = 'Signed by '.$name;
        }
        $item->save();
        if ($item->proposed_price !== null) {
            $this->applySignedPrice($item);
        }
        $order = $item->order ?: RestaurantOrder::find($item->restaurant_order_id);
        $this->event($order, $item, 'client_signed', null, $name);

        return $item;
    }

    public function assignWaitress(RestaurantOrder $order, $userId, User $actor)
    {
        $staffId = $this->staffId($userId);
        $order->waitress_user_id = $staffId;
        $order->save();
        $this->event($order, null, 'waitress_assigned', $actor->id, $staffId ? 'user '.$staffId : 'cleared');

        return $order;
    }

    public function sendReviewLink(RestaurantOrder $order)
    {
        if ($order->review_sent_at) {
            return;
        }
        if (OrderReview::where('sale_id', $order->sale_id)->exists()) {
            return;
        }
        $order->review_sent_at = now();
        $order->save();
        $sale = $order->sale ?: Sale::find($order->sale_id);
        $ref = $sale ? $sale->reference_no : $order->sale_id;
        $this->notify($order, 'Please review order '.$ref.'. Cleanliness, delivery time, service and food: '.url('/reviews?ref='.urlencode((string) $ref)));
    }

    public function notifyReceived(RestaurantOrder $order)
    {
        $sale = $order->sale ?: Sale::find($order->sale_id);
        if (! $sale) {
            return;
        }
        $lines = $order->items->map(function ($item) {
            return $item->product_name.' x'.$this->qty($item);
        })->implode(', ');
        $text = $this->brand().' received order '.$sale->reference_no.' ('.$order->serviceLabel().').';
        if ($lines !== '') {
            $text .= ' '.$lines.'.';
        }
        $text .= ' Total '.number_format((float) $sale->grand_total, 0).'. Track: '.$order->trackUrl();
        $this->notify($order, $text);
    }

    protected function notify(RestaurantOrder $order, $body)
    {
        try {
            $sale = $order->relationLoaded('sale') ? $order->sale : Sale::find($order->sale_id);
            if (! $sale) {
                return;
            }
            $customer = $sale->relationLoaded('customer') ? $sale->customer : Customer::find($sale->customer_id);
            if (! $customer || ! $customer->phone_number) {
                return;
            }
            app(\App\Services\Messaging\NotificationRouter::class)
                ->sendWhatsAppText($customer->phone_number, $body."\n".$order->trackUrl());
        } catch (\Throwable $e) {
            \Log::warning('restaurant notify: '.$e->getMessage());
        }
    }

    public function attachWebsiteOrder($order, array $cart)
    {
        if (! $order || ! Schema::hasTable('restaurant_orders')) {
            return null;
        }
        $token = 'web-order-'.$order->id;
        $existing = RestaurantOrder::where('checkout_token', $token)->first();
        if ($existing) {
            return $existing;
        }

        $prepared = [];
        $queued = false;
        foreach ($cart as $item) {
            $productId = isset($item['products_id']) ? $item['products_id'] : (isset($item['product_id']) ? $item['product_id'] : null);
            $qty = isset($item['quantity']) ? (float) $item['quantity'] : (isset($item['qty']) ? (float) $item['qty'] : 0);
            if (! $productId || $qty <= 0) {
                continue;
            }
            $product = Product::where('is_active', true)->find($productId);
            if (! $product) {
                continue;
            }
            $lane = RestaurantDepartment::laneForCategoryId($product->category_id);
            if ($lane) {
                $queued = true;
            }
            $price = isset($item['price']) ? (float) $item['price'] : (float) $product->price;
            $prepared[] = ['product' => $product, 'qty' => $qty, 'price' => $price];
        }
        if (! $queued) {
            return null;
        }

        $phone = isset($order->phone) ? $order->phone : '';
        try {
            $phone = \App\Support\WhatsAppPhone::normalize($phone);
        } catch (\Throwable $e) {
            $phone = preg_replace('/\D/', '', (string) $phone);
        }
        if ($phone === '') {
            return null;
        }
        $customer = Customer::where('phone_number', $phone)->first();
        if (! $customer) {
            $group = CustomerGroup::query()->orderBy('id')->first();
            $customer = Customer::create([
                'customer_group_id' => $group ? $group->id : 1,
                'name' => $order->name ?: 'Guest',
                'phone_number' => $phone,
                'email' => $order->email ?: null,
                'address' => $order->address ?: 'Website',
                'city' => $order->city ?: 'Kigali',
                'is_active' => 1,
            ]);
        }

        $warehouse = Warehouse::query()->orderBy('id')->first();
        $biller = Biller::query()->orderBy('id')->first();
        if (! $warehouse || ! $biller) {
            return null;
        }

        $totalQty = 0;
        $totalPrice = 0;
        foreach ($prepared as $line) {
            $totalQty += $line['qty'];
            $totalPrice += $line['price'] * $line['qty'];
        }
        $shipping = isset($order->shipping_charges) ? (float) $order->shipping_charges : 0;
        $sale = Sale::create([
            'reference_no' => $this->reference(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'biller_id' => $biller->id,
            'user_id' => null,
            'item' => count($prepared),
            'total_qty' => $totalQty,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => $totalPrice,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
            'shipping_cost' => $shipping,
            'grand_total' => $totalPrice + $shipping,
            'sale_status' => 1,
            'payment_status' => 1,
            'paid_amount' => 0,
            'paid_by_id' => 9,
            'sale_note' => 'Website order '.$order->id,
        ]);

        foreach ($prepared as $line) {
            Product_Sale::create([
                'sale_id' => $sale->id,
                'product_id' => $line['product']->id,
                'category_id' => $line['product']->category_id,
                'warehouse_id' => $warehouse->id,
                'qty' => $line['qty'],
                'sale_unit_id' => $line['product']->sale_unit_id ?: 0,
                'net_unit_price' => $line['price'],
                'discount' => 0,
                'tax_rate' => 0,
                'tax' => 0,
                'total' => $line['price'] * $line['qty'],
            ]);
        }

        return $this->attachFromRequest($sale, Request::create('/', 'POST', [
            'service_type' => 'take_away',
            'order_source' => 'frontend',
            'checkout_token' => $token,
            'customer_comment' => $order->address ?: null,
        ]));
    }

    public function markWebsiteOrderPaid($order)
    {
        if (! $order || ! Schema::hasTable('restaurant_orders')) {
            return;
        }
        $restaurant = RestaurantOrder::where('checkout_token', 'web-order-'.$order->id)->first();
        if (! $restaurant) {
            return;
        }
        $sale = Sale::find($restaurant->sale_id);
        if (! $sale || (int) $sale->payment_status === 4) {
            return;
        }
        $sale->paid_amount = $sale->grand_total;
        $sale->payment_status = 4;
        $sale->save();
    }

    protected function makeItem(RestaurantOrder $order, array $data)
    {
        $row = [
            'restaurant_order_id' => $order->id,
            'product_sale_id' => isset($data['product_sale_id']) ? $data['product_sale_id'] : null,
            'product_id' => isset($data['product_id']) ? $data['product_id'] : null,
            'product_name' => $data['product_name'],
            'department' => $data['department'],
            'qty' => $data['qty'],
            'status' => 'awaiting',
            'customer_comment' => isset($data['customer_comment']) ? $data['customer_comment'] : null,
            'attempt_no' => isset($data['attempt_no']) ? $data['attempt_no'] : 1,
            'remake_of_id' => isset($data['remake_of_id']) ? $data['remake_of_id'] : null,
        ];
        if (Schema::hasColumn('restaurant_order_items', 'lane')) {
            $row['lane'] = isset($data['lane']) ? $data['lane'] : null;
        }

        return RestaurantOrderItem::create($row);
    }

    protected function event(RestaurantOrder $order, $item, $action, $userId, $note)
    {
        if (! $order) {
            return;
        }
        RestaurantOrderEvent::create([
            'restaurant_order_id' => $order->id,
            'restaurant_order_item_id' => $item ? $item->id : null,
            'action' => $action,
            'user_id' => $userId,
            'note' => $note,
        ]);
        RestaurantAccess::forgetCounts();
    }

    protected function assertOpenForChange(RestaurantOrderItem $item)
    {
        if (! in_array($item->status, ['awaiting', 'preparing', 'ready'], true)) {
            throw new \InvalidArgumentException('This item can no longer be changed.');
        }
    }

    protected function assertChangeColumns()
    {
        if (! Schema::hasColumn('restaurant_order_items', 'proposed_price')) {
            throw new \InvalidArgumentException('Price changes are not available until the latest update is applied.');
        }
    }

    protected function dropLineFromBill(RestaurantOrderItem $item)
    {
        if (! $item->product_sale_id) {
            return;
        }
        $line = Product_Sale::find($item->product_sale_id);
        if (! $line) {
            return;
        }
        $amount = (float) $line->total;
        $line->total = 0;
        $line->net_unit_price = 0;
        $line->tax = 0;
        $line->save();
        if ($amount <= 0) {
            return;
        }
        $order = $item->order ?: RestaurantOrder::find($item->restaurant_order_id);
        $sale = $order ? ($order->sale ?: Sale::find($order->sale_id)) : Sale::find($line->sale_id);
        if (! $sale) {
            return;
        }
        $sale->grand_total = max(0, round((float) $sale->grand_total - $amount, 2));
        $sale->total_price = max(0, round((float) $sale->total_price - $amount, 2));
        $sale->save();
    }

    protected function applySignedPrice(RestaurantOrderItem $item)
    {
        if (! $item->product_sale_id) {
            return;
        }
        $line = Product_Sale::find($item->product_sale_id);
        if (! $line) {
            return;
        }
        $qty = (float) $line->qty;
        if ($qty <= 0) {
            $qty = (float) $item->qty ?: 1;
        }
        $old = (float) $line->total;
        $new = round((float) $item->proposed_price * $qty, 2);
        $line->net_unit_price = (float) $item->proposed_price;
        $line->total = $new;
        $line->save();
        $order = $item->order ?: RestaurantOrder::find($item->restaurant_order_id);
        $sale = $order ? ($order->sale ?: Sale::find($order->sale_id)) : Sale::find($line->sale_id);
        if (! $sale) {
            return;
        }
        $sale->grand_total = max(0, round((float) $sale->grand_total - $old + $new, 2));
        $sale->total_price = max(0, round((float) $sale->total_price - $old + $new, 2));
        $sale->save();
    }

    protected function restoreItemStock(RestaurantOrderItem $item)
    {
        $product = Product::find($item->product_id);
        if (! $product) {
            return;
        }
        $order = $item->order ?: RestaurantOrder::find($item->restaurant_order_id);
        $sale = $order ? ($order->sale ?: Sale::find($order->sale_id)) : null;
        $warehouseId = $sale ? $sale->warehouse_id : null;
        $this->restoreStock($product, $item->qty, $warehouseId);
    }

    protected function restoreStock(Product $product, $qty, $warehouseId)
    {
        $quantity = $this->baseQty($product, $qty);
        if ($product->type === 'combo' && $product->product_list) {
            $ids = explode(',', $product->product_list);
            $qtyList = explode(',', (string) $product->qty_list);
            foreach ($ids as $key => $childId) {
                $child = Product::find($childId);
                if (! $child) {
                    continue;
                }
                $childQty = $quantity * (isset($qtyList[$key]) ? (float) $qtyList[$key] : 1);
                $this->returnQty($child, $childQty, $warehouseId);
            }

            return;
        }
        $this->returnQty($product, $quantity, $warehouseId);
    }

    protected function returnQty(Product $product, $quantity, $warehouseId)
    {
        $product->qty = (float) $product->qty + (float) $quantity;
        $product->save();
        if (! $warehouseId) {
            return;
        }
        $bin = Product_Warehouse::where('product_id', $product->id)->where('warehouse_id', $warehouseId)->first();
        if ($bin) {
            $bin->qty = (float) $bin->qty + (float) $quantity;
            $bin->save();
        }
    }

    protected function assertStatus(RestaurantOrderItem $item, $from, $to)
    {
        if ($item->status !== $from) {
            throw new \InvalidArgumentException('This item is already '.$item->status.' and cannot move to '.$to.'.');
        }
    }

    protected function staffId($id)
    {
        $id = (int) $id;
        if ($id < 1) {
            return null;
        }

        return User::where('id', $id)->exists() ? $id : null;
    }

    protected function tableId($id)
    {
        $id = (int) $id;
        if ($id < 1) {
            return null;
        }

        return RestaurantTable::where('id', $id)->where('is_active', 1)->exists() ? $id : null;
    }

    protected function token()
    {
        return bin2hex(random_bytes(20));
    }

    protected function reference()
    {
        do {
            $ref = 'rst-'.date('Ymd').'-'.date('His').'-'.mt_rand(10, 99);
        } while (Sale::where('reference_no', $ref)->exists());

        return $ref;
    }

    protected function priceLine(Product $product, $qty)
    {
        $unit = (float) $product->price;
        $rate = 0;
        if ($product->tax_id) {
            $tax = \App\Tax::find($product->tax_id);
            if ($tax) {
                $rate = (float) $tax->rate;
            }
        }
        $net = $unit * (float) $qty;
        $taxAmount = $net * $rate / 100;

        return ['unit' => $unit, 'net' => $net, 'rate' => $rate, 'tax' => $taxAmount];
    }

    protected function deductStock(Product $product, $qty, $warehouseId)
    {
        $quantity = $this->baseQty($product, $qty);
        if ($product->type === 'combo' && $product->product_list) {
            $ids = explode(',', $product->product_list);
            $qtyList = explode(',', (string) $product->qty_list);
            foreach ($ids as $key => $childId) {
                $child = Product::find($childId);
                if (! $child) {
                    continue;
                }
                $childQty = $quantity * (isset($qtyList[$key]) ? (float) $qtyList[$key] : 1);
                $this->applyQty($child, $childQty, $warehouseId);
            }

            return;
        }
        $this->applyQty($product, $quantity, $warehouseId);
    }

    protected function baseQty(Product $product, $qty)
    {
        $quantity = (float) $qty;
        if (! $product->sale_unit_id) {
            return $quantity;
        }
        $unit = Unit::find($product->sale_unit_id);
        if (! $unit) {
            return $quantity;
        }
        if ($unit->operator === '*') {
            return $quantity * (float) $unit->operation_value;
        }
        if ($unit->operator === '/' && (float) $unit->operation_value != 0) {
            return $quantity / (float) $unit->operation_value;
        }

        return $quantity;
    }

    protected function applyQty(Product $product, $quantity, $warehouseId)
    {
        $product->qty = (float) $product->qty - (float) $quantity;
        $product->save();
        $bin = Product_Warehouse::where('product_id', $product->id)->where('warehouse_id', $warehouseId)->first();
        if ($bin) {
            $bin->qty = (float) $bin->qty - (float) $quantity;
            if ($bin->qty < 0) {
                $bin->qty = 0;
            }
            $bin->save();
        }
    }

    protected function brand()
    {
        try {
            return \App\Support\SiteBrand::companyName();
        } catch (\Throwable $e) {
            return 'Welcome 2 Kigali';
        }
    }

    protected function qty(RestaurantOrderItem $item)
    {
        $qty = (float) $item->qty;

        return floor($qty) == $qty ? (string) (int) $qty : (string) $qty;
    }
}
