<?php

namespace App\Http\Controllers;

use App\MealRejection;
use App\Product_Sale;
use App\RestaurantOrder;
use App\RestaurantOrderItem;
use App\RestaurantTable;
use App\Sale;
use App\User;
use App\Services\Restaurant\RestaurantAccess;
use App\Services\Restaurant\RestaurantOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class RestaurantOrderController extends Controller
{
    protected $orders;

    public function __construct(RestaurantOrderService $orders)
    {
        $this->orders = $orders;
    }

    public function board(Request $request)
    {
        if (! RestaurantAccess::canSeeOrders($request->user())) {
            return redirect('admin')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        $lanes = [];
        foreach (RestaurantAccess::lanes() as $key => $label) {
            if (RestaurantAccess::seeLane($request->user(), $key)) {
                $lanes[$key] = $label;
            }
        }
        if (! $lanes) {
            return redirect('admin')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        $lane = $request->input('lane', key($lanes));
        if (! isset($lanes[$lane])) {
            $lane = key($lanes);
        }
        $tabs = [];
        foreach (RestaurantAccess::statuses() as $key => $label) {
            if (RestaurantAccess::seeStatus($request->user(), $key)) {
                $tabs[$key] = $label;
            }
        }
        if (! $tabs) {
            $tabs = RestaurantAccess::statuses();
        }
        $tab = $request->input('tab', 'awaiting');
        if ($tab === 'preparing') {
            $tab = 'accepted';
        }
        if (! isset($tabs[$tab])) {
            $tab = key($tabs);
        }
        $status = $tab === 'accepted' ? 'preparing' : $tab;
        $items = collect();
        if (Schema::hasColumn('restaurant_order_items', 'lane')) {
            $items = RestaurantOrderItem::with(['order.sale.customer', 'order.diningTable', 'order.waitress', 'acceptedBy', 'productSale'])
                ->where('status', $status)
                ->where(function ($query) use ($lane) {
                    $query->where('lane', $lane);
                    if ($lane === 'restaurant') {
                        $query->orWhere(function ($fallback) {
                            $fallback->whereNull('lane')->where('department', 'food');
                        });
                    }
                    if ($lane === 'bar') {
                        $query->orWhere(function ($fallback) {
                            $fallback->whereNull('lane')->where('department', 'drinks');
                        });
                    }
                })
                ->orderBy('id', $status === 'delivered' ? 'desc' : 'asc')
                ->limit($status === 'delivered' ? 40 : 80)
                ->get();
        }

        return view('restaurant.queue', [
            'title' => 'Orders',
            'lane' => $lane,
            'lanes' => $lanes,
            'tab' => $tab,
            'tabs' => $tabs,
            'items' => $items,
            'canReject' => RestaurantAccess::can($request->user(), 'meal-rejections'),
            'canWait' => RestaurantAccess::can($request->user(), 'waiter-orders'),
        ]);
    }

    public function kitchen(Request $request)
    {
        return redirect()->route('restaurant.orders', ['lane' => 'restaurant', 'tab' => $request->input('tab', 'awaiting')]);
    }

    public function bar(Request $request)
    {
        return redirect()->route('restaurant.orders', ['lane' => 'bar', 'tab' => $request->input('tab', 'awaiting')]);
    }

    public function ready(Request $request)
    {
        return redirect()->route('restaurant.orders', ['lane' => 'coffee', 'tab' => 'ready']);
    }

    public function accept(Request $request, $item)
    {
        $row = $this->item($item);
        if ($deny = $this->denyOrder($request, $row, 'add')) {
            return $deny;
        }
        try {
            $this->orders->accept($row, $request->user(), $request->input('minutes'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return back()->with('message', 'Accepted. The item is now being prepared.');
    }

    public function hold(Request $request, $item)
    {
        $row = $this->item($item);
        if ($deny = $this->denyOrder($request, $row, 'edit')) {
            return $deny;
        }
        try {
            $this->orders->hold($row, $request->user(), $request->input('comment', $request->input('note')));
        } catch (\InvalidArgumentException $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return back()->with('message', 'Order put on hold.');
    }

    public function resume(Request $request, $item)
    {
        $row = $this->item($item);
        if ($deny = $this->denyOrder($request, $row, 'edit')) {
            return $deny;
        }
        try {
            $this->orders->resume($row, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return back()->with('message', 'Order taken off hold.');
    }

    public function comment(Request $request, $item)
    {
        $row = $this->item($item);
        if ($deny = $this->denyOrder($request, $row, 'edit')) {
            return $deny;
        }
        try {
            $this->orders->updateItemComment($row, $request->user(), $request->input('comment', $request->input('note')));
        } catch (\InvalidArgumentException $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return back()->with('message', 'Comment saved.');
    }

    public function estimate(Request $request, $item)
    {
        $row = $this->item($item);
        if ($deny = $this->denyOrder($request, $row, 'edit')) {
            return $deny;
        }
        try {
            $this->orders->reviseEstimate($row, $request->user(), $request->input('minutes'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return back()->with('message', 'Preparation time updated.');
    }

    public function markReady(Request $request, $item)
    {
        $row = $this->item($item);
        if ($deny = $this->denyOrder($request, $row, 'edit')) {
            return $deny;
        }
        try {
            $this->orders->markReady($row, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return back()->with('message', 'Marked ready for delivery.');
    }

    public function deliver(Request $request, $item)
    {
        $row = $this->item($item);
        if ($deny = $this->denyOrder($request, $row, 'edit')) {
            return $deny;
        }
        try {
            $this->orders->deliver($row, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return back()->with('message', 'Marked delivered. The guest has been notified.');
    }

    public function reject(Request $request, $item)
    {
        $row = $this->item($item);
        if ($deny = $this->denyOrder($request, $row, 'delete')) {
            return $deny;
        }
        try {
            $this->orders->reject(
                $row,
                $request->user(),
                $request->input('qty'),
                $request->input('reason'),
                $request->input('resolution')
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return back()->with('message', 'Rejection recorded. Use Return Sale if a refund or price change is authorised.');
    }

    public function deny(Request $request, $item)
    {
        $row = $this->item($item);
        if ($deny = $this->denyOrder($request, $row, 'delete')) {
            return $deny;
        }
        try {
            $this->orders->deny($row, $request->user(), $request->input('reason'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return back()->with('message', 'Order denied. It will not be billed.');
    }

    public function price(Request $request, $item)
    {
        $row = $this->item($item);
        if ($deny = $this->denyOrder($request, $row, 'edit')) {
            return $deny;
        }
        try {
            $this->orders->proposePrice($row, $request->user(), $request->input('price'), $request->input('note'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return back()->with('message', 'New price sent. The guest must sign before it replaces the original price.');
    }

    public function modification(Request $request, $item)
    {
        $row = $this->item($item);
        if ($deny = $this->denyOrder($request, $row, 'edit')) {
            return $deny;
        }
        try {
            $this->orders->requestModification($row, $request->user(), $request->input('note'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return back()->with('message', 'Change sent. The guest must sign on the tracking page.');
    }

    public function alerts(Request $request)
    {
        $user = $request->user();
        if (! $user || ! RestaurantAccess::installed()) {
            return response()->json(['items' => [], 'summary' => [], 'pending' => 0]);
        }
        // Every logged-in staff member should hear new orders. Prefer their lane/status
        // permissions when set; otherwise notify on all cafe lanes.
        $statuses = [];
        if (RestaurantAccess::seeStatus($user, 'awaiting')) {
            $statuses[] = 'awaiting';
        }
        if (RestaurantAccess::seeStatus($user, 'ready')) {
            $statuses[] = 'ready';
        }
        if (! $statuses) {
            $statuses = ['awaiting', 'ready'];
        }
        $lanes = [];
        foreach (array_keys(RestaurantAccess::lanes()) as $lane) {
            if (RestaurantAccess::seeLane($user, $lane)) {
                $lanes[] = $lane;
            }
        }
        if (! $lanes) {
            $lanes = array_keys(RestaurantAccess::lanes());
        }
        $rows = RestaurantOrderItem::with(['order.sale'])
            ->whereIn('status', $statuses)
            ->where(function ($query) use ($lanes) {
                $query->whereIn('lane', $lanes);
                if (in_array('restaurant', $lanes, true)) {
                    $query->orWhereNull('lane');
                }
            })
            ->orderBy('id', 'desc')
            ->limit(12)
            ->get();
        $items = [];
        $pendingByLane = [];
        foreach ($rows as $row) {
            $sale = $row->order ? $row->order->sale : null;
            $lane = $row->lane ?: 'restaurant';
            $tab = $row->status === 'ready' ? 'ready' : 'awaiting';
            if (! isset($pendingByLane[$lane])) {
                $pendingByLane[$lane] = ['awaiting' => 0, 'ready' => 0];
            }
            if ($row->status === 'ready') {
                $pendingByLane[$lane]['ready']++;
            } else {
                $pendingByLane[$lane]['awaiting']++;
            }
            $items[] = [
                'id' => (int) $row->id,
                'name' => $row->product_name,
                'qty' => $row->qty,
                'status' => $row->status,
                'lane' => $lane,
                'lane_label' => RestaurantAccess::lanes()[$lane] ?? $lane,
                'ref' => $sale ? $sale->reference_no : '',
                'href' => route('restaurant.orders', [
                    'lane' => $lane,
                    'tab' => $tab,
                ]),
            ];
        }

        $counts = RestaurantAccess::queueCounts();
        $summary = [];
        foreach ($lanes as $lane) {
            $awaiting = (int) ($counts[$lane]['awaiting'] ?? 0);
            $ready = (int) ($counts[$lane]['ready'] ?? 0);
            if ($awaiting + $ready < 1) {
                continue;
            }
            $openTab = $awaiting > 0 ? 'awaiting' : 'ready';
            $summary[] = [
                'lane' => $lane,
                'label' => RestaurantAccess::lanes()[$lane] ?? $lane,
                'awaiting' => $awaiting,
                'ready' => $ready,
                'total' => $awaiting + $ready,
                'href' => route('restaurant.orders', ['lane' => $lane, 'tab' => $openTab]),
            ];
        }

        return response()->json([
            'items' => $items,
            'summary' => $summary,
            'pending' => array_sum(array_map(function ($row) {
                return (int) ($row['total'] ?? 0);
            }, $summary)),
        ]);
    }

    public function assignWaitress(Request $request, $order)
    {
        if (! RestaurantAccess::can($request->user(), 'waiter-orders') && ! RestaurantAccess::can($request->user(), 'order-logs')) {
            return redirect('/')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        $row = RestaurantOrder::findOrFail($order);
        $this->orders->assignWaitress($row, $request->input('waitress_user_id'), $request->user());

        return back()->with('message', 'Waitress / waiter updated.');
    }

    public function confirmation($sale)
    {
        $user = auth()->user();
        if (! RestaurantAccess::can($user, 'sales-index') && ! RestaurantAccess::can($user, 'waiter-orders') && ! RestaurantAccess::can($user, 'kitchen-orders') && ! RestaurantAccess::can($user, 'bar-orders')) {
            return redirect('admin')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        $sale = Sale::with('customer')->findOrFail($sale);
        $order = RestaurantOrder::with(['items', 'diningTable', 'waitress'])->where('sale_id', $sale->id)->first();
        $lines = Product_Sale::where('sale_id', $sale->id)->get();
        $staff = RestaurantAccess::usersWithPermission('waiter-orders');

        return view('restaurant.confirmation', compact('sale', 'order', 'lines', 'staff'));
    }

    public function logs(Request $request)
    {
        if (! RestaurantAccess::can($request->user(), 'order-logs')) {
            return redirect('admin')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        $query = RestaurantOrderItem::with(['order.sale.customer', 'order.diningTable', 'order.placedBy', 'order.waitress', 'acceptedBy', 'readyBy', 'deliveredBy']);
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }
        if (in_array($request->input('department'), ['food', 'drinks'], true)) {
            $query->where('department', $request->input('department'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('table')) {
            $table = $request->input('table');
            $query->whereHas('order', function ($q) use ($table) {
                $q->where('restaurant_table_id', $table);
            });
        }
        if ($request->filled('staff')) {
            $staff = (int) $request->input('staff');
            $query->where(function ($q) use ($staff) {
                $q->where('accepted_by', $staff)->orWhere('ready_by', $staff)->orWhere('delivered_by', $staff)
                    ->orWhereHas('order', function ($order) use ($staff) {
                        $order->where('placed_by_user_id', $staff)->orWhere('waitress_user_id', $staff);
                    });
            });
        }
        $items = $query->orderByDesc('id')->limit(300)->get();
        $rejections = MealRejection::with('user')->orderByDesc('id')->limit(100)->get();
        $tables = RestaurantTable::orderBy('name')->get();
        $staffList = collect();
        foreach (['kitchen-orders', 'bar-orders', 'waiter-orders', 'order-logs', 'sales-add'] as $permission) {
            $staffList = $staffList->merge(RestaurantAccess::usersWithPermission($permission));
        }
        $staffList = $staffList->unique('id')->sortBy('name')->values();

        return view('restaurant.logs', compact('items', 'rejections', 'tables', 'staffList'));
    }

    public function storeTable(Request $request)
    {
        if (! RestaurantAccess::can($request->user(), 'order-logs')) {
            return redirect('/')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        $name = trim((string) $request->input('name'));
        if ($name === '') {
            return back()->with('not_permitted', 'Table name is required.');
        }
        RestaurantTable::create(['name' => $name, 'is_active' => 1]);

        return back()->with('message', 'Table added.');
    }

    protected function item($id)
    {
        return RestaurantOrderItem::with('order')->findOrFail($id);
    }

    protected function denyOrder(Request $request, RestaurantOrderItem $item, $action)
    {
        if (RestaurantAccess::canOrder($request->user(), $action, $item)) {
            return null;
        }

        return redirect('/')->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }
}
