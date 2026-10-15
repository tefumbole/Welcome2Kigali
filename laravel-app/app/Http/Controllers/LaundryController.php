<?php

namespace App\Http\Controllers;

use App\Customer;
use App\LaundryOrder;
use App\Services\LaundryService;
use App\Support\LaundryQr;
use App\Support\StaffAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Role;

class LaundryController extends Controller
{
    protected $laundry;
    protected $all_permission = [];

    public function __construct(LaundryService $laundry)
    {
        $this->laundry = $laundry;
        $this->middleware(function ($request, $next) {
            if (Auth::check()) {
                $role = Role::find(Auth::user()->role_id);
                if ($role) {
                    try {
                        foreach (Role::findByName($role->name)->permissions as $permission) {
                            $this->all_permission[] = $permission->name;
                        }
                    } catch (\Throwable $e) {
                    }
                }
            }
            View::share('all_permission', $this->all_permission);

            return $next($request);
        });
    }

    public function received()
    {
        $this->authorizeLaundry('laundry.received');
        $orders = LaundryOrder::with(['customer', 'items', 'sale'])
            ->where('status', LaundryOrder::STATUS_RECEIVED)
            ->orderBy('customer_id')
            ->orderByDesc('id')
            ->paginate(30);

        return view('laundry.received', compact('orders'));
    }

    public function storeReceived(Request $request)
    {
        $this->authorizeLaundry('laundry.received');
        $request->validate([
            'customer_id' => 'required|integer',
            'product_id' => 'required|array',
            'qty' => 'required|array',
        ]);
        $items = [];
        foreach ($request->input('product_id', []) as $i => $productId) {
            if (! $productId) {
                continue;
            }
            $items[] = [
                'product_id' => $productId,
                'qty' => $request->input('qty.'.$i, 1),
                'note' => $request->input('item_note.'.$i, ''),
            ];
        }
        try {
            $order = $this->laundry->createFromIntake([
                'customer_id' => $request->input('customer_id'),
                'items' => $items,
                'notes' => $request->input('notes'),
                'pay_now' => (bool) $request->input('pay_now'),
            ]);
        } catch (\Throwable $e) {
            return back()->with('not_permitted', $e->getMessage())->withInput();
        }

        return redirect()->route('laundry.received')->with('message', 'Laundry ticket '.$order->reference_no.' is awaiting acceptance.');
    }

    public function accept(Request $request, $id)
    {
        $this->authorizeLaundry('laundry.received');
        $order = LaundryOrder::with('items')->findOrFail($id);
        $ids = $request->input('item_id', []);
        if (! is_array($ids) || ! $ids) {
            $ids = $order->items->pluck('id')->all();
        }
        $itemTimes = [];
        foreach ($ids as $itemId) {
            $itemTimes[$itemId] = $request->input('item_collect_at.'.$itemId, $request->input('collect_at'));
        }
        try {
            $this->laundry->acceptForCleaning($order, $request->input('collect_at'), $itemTimes);
        } catch (\Throwable $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return redirect()->route('laundry.received')->with('message', $order->reference_no.' accepted. The customer has left this list and the client has been notified.');
    }

    public function cleaning()
    {
        $this->authorizeLaundry('laundry.cleaning');
        $orders = LaundryOrder::with(['customer', 'items', 'sale'])
            ->where('status', LaundryOrder::STATUS_CLEANING)
            ->orderBy('customer_id')
            ->orderByDesc('id')
            ->paginate(30);

        return view('laundry.cleaning', compact('orders'));
    }

    public function startCleaning(Request $request, $id)
    {
        $this->authorizeLaundry('laundry.cleaning');
        $order = LaundryOrder::findOrFail($id);
        try {
            $this->laundry->acceptForCleaning($order, $request->input('collect_at'), $request->input('item_collect_at', []));
        } catch (\Throwable $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return redirect()->route('laundry.cleaning')->with('message', $order->reference_no.' is awaiting cleaning.');
    }

    public function markReady($id)
    {
        $this->authorizeLaundry('laundry.cleaning');
        $order = LaundryOrder::findOrFail($id);
        try {
            $this->laundry->markReady($order);
        } catch (\Throwable $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return redirect()->route('laundry.ready')->with('message', $order->reference_no.' is ready for collection.');
    }

    public function ready()
    {
        $this->authorizeLaundry('laundry.ready');
        $orders = LaundryOrder::with(['customer', 'items', 'sale'])
            ->where('status', LaundryOrder::STATUS_READY)
            ->orderBy('customer_id')
            ->orderByDesc('id')
            ->paginate(30);

        return view('laundry.ready', compact('orders'));
    }

    public function pay(Request $request, $id)
    {
        $this->authorizeLaundry('laundry.ready');
        $order = LaundryOrder::with('sale')->findOrFail($id);
        $amount = $request->input('amount', $order->dueAmount());
        $method = $request->input('method') === 'momo' ? 'MoMo' : 'Cash';
        try {
            $this->laundry->addPayment($order, $amount, $method, 'Laundry '.$method.' payment');
        } catch (\Throwable $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return back()->with('message', $method.' payment recorded for '.$order->reference_no.'.');
    }

    public function sendCollect($id)
    {
        $this->authorizeLaundry('laundry.collect');
        $order = LaundryOrder::findOrFail($id);
        try {
            $this->laundry->sendCollectLink($order);
        } catch (\Throwable $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return redirect()->route('laundry.collect')->with('message', 'Collection link sent for '.$order->reference_no.'.');
    }

    public function collect()
    {
        $this->authorizeLaundry('laundry.collect');
        $awaiting = LaundryOrder::with(['customer', 'items', 'sale'])
            ->where('status', LaundryOrder::STATUS_AWAITING_SIGNATURE)
            ->orderBy('customer_id')
            ->orderByDesc('id')
            ->get();
        $collected = LaundryOrder::with(['customer', 'items', 'sale'])
            ->where('status', LaundryOrder::STATUS_COLLECTED)
            ->orderBy('customer_id')
            ->orderByDesc('collected_at')
            ->paginate(30);

        return view('laundry.collect', compact('awaiting', 'collected'));
    }

    public function show($id)
    {
        $this->authorizeLaundry();
        $order = LaundryOrder::with(['customer', 'items', 'sale', 'user'])->findOrFail($id);
        $qr = LaundryQr::dataUri($order->qr_token);

        return view('laundry.show', compact('order', 'qr'));
    }

    public function customerPrice(Request $request)
    {
        $this->authorizeLaundry('laundry.received');
        $customerId = (int) $request->query('customer_id');
        $products = $this->laundry->laundryProducts();
        $out = [];
        foreach ($products as $product) {
            $out[] = [
                'id' => $product->id,
                'name' => $product->name,
                'list_price' => (float) $product->price,
                'unit_price' => $this->laundry->memberUnitPrice($customerId, $product),
            ];
        }

        return response()->json(['products' => $out]);
    }

    protected function authorizeLaundry($permission = 'laundry_module')
    {
        if ($this->hasLaundryAccess($permission)) {
            return;
        }
        abort(403, 'You are not allowed to access Laundry.');
    }

    protected function hasLaundryAccess($permission)
    {
        if (StaffAccess::canManageSite()) {
            return true;
        }
        $role = Auth::user() ? Role::find(Auth::user()->role_id) : null;
        $name = strtolower((string) optional($role)->name);
        if (in_array($name, ['admin', 'owner', 'super admin', 'super_admin', 'system'], true)) {
            return true;
        }

        return in_array('laundry_module', $this->all_permission, true)
            || in_array($permission, $this->all_permission, true);
    }
}
