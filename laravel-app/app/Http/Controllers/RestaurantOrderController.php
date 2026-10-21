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
        $lanes = [
            'coffee' => 'Coffee & Tea',
            'restaurant' => 'Restaurant',
            'bar' => 'Bar',
            'online' => 'Online orders',
        ];
        $lane = $request->input('lane', 'coffee');
        if (! isset($lanes[$lane])) {
            $lane = 'coffee';
        }
        $tabs = [
            'awaiting' => 'Awaiting',
            'accepted' => 'Accepted',
            'ready' => 'Ready for Delivery',
            'delivered' => 'Delivered',
        ];
        $tab = $request->input('tab', 'awaiting');
        if ($tab === 'preparing') {
            $tab = 'accepted';
        }
        if (! isset($tabs[$tab])) {
            $tab = 'awaiting';
        }
        $status = $tab === 'accepted' ? 'preparing' : $tab;
        $items = collect();
        if (Schema::hasColumn('restaurant_order_items', 'lane')) {
            $items = RestaurantOrderItem::with(['order.sale.customer', 'order.diningTable', 'order.waitress', 'acceptedBy'])
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
                ->orderBy('id')
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
        if ($deny = $this->denyItem($request, $row)) {
            return $deny;
        }
        try {
            $this->orders->accept($row, $request->user(), $request->input('minutes'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return back()->with('message', 'Accepted. The item is now being prepared.');
    }

    public function estimate(Request $request, $item)
    {
        $row = $this->item($item);
        if ($deny = $this->denyItem($request, $row)) {
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
        if ($deny = $this->denyItem($request, $row)) {
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
        if (! RestaurantAccess::can($request->user(), 'waiter-orders') && ! RestaurantAccess::canItem($request->user(), $row)) {
            return redirect('/')->with('not_permitted', 'Sorry! You are not allowed to access this module');
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
        if (! RestaurantAccess::can($request->user(), 'meal-rejections')) {
            return redirect('/')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        $row = $this->item($item);
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

    protected function denyItem(Request $request, RestaurantOrderItem $item)
    {
        if (RestaurantAccess::canItem($request->user(), $item)) {
            return null;
        }

        return redirect('/')->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }
}
