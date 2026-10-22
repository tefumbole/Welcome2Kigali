<?php

namespace App\Http\Controllers;

use App\RestaurantTable;
use App\Services\Restaurant\RestaurantAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TableController extends Controller
{
    public function index()
    {
        if (! $this->canManage()) {
            return redirect('admin')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        $tables = RestaurantTable::orderBy('name')->get();

        return view('tables.index', compact('tables'));
    }

    public function store(Request $request)
    {
        if (! $this->canManage(true)) {
            return redirect('admin')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        $name = trim((string) $request->input('name'));
        if ($name === '') {
            return back()->with('not_permitted', 'Table name is required.');
        }
        RestaurantTable::create([
            'name' => $name,
            'description' => trim((string) $request->input('description')) ?: null,
            'capacity' => $request->filled('capacity') ? max(1, (int) $request->input('capacity')) : null,
            'is_active' => $request->input('is_active', 1) ? 1 : 0,
        ]);

        return back()->with('message', 'Table added.');
    }

    public function update(Request $request, $id)
    {
        if (! $this->canManage(true)) {
            return redirect('admin')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        $table = RestaurantTable::findOrFail($id);
        $name = trim((string) $request->input('name'));
        if ($name === '') {
            return back()->with('not_permitted', 'Table name is required.');
        }
        $table->name = $name;
        $table->description = trim((string) $request->input('description')) ?: null;
        $table->capacity = $request->filled('capacity') ? max(1, (int) $request->input('capacity')) : null;
        $table->is_active = $request->input('is_active') ? 1 : 0;
        $table->save();

        return back()->with('message', 'Table updated.');
    }

    public function destroy($id)
    {
        if (! RestaurantAccess::can(Auth::user(), 'tables-delete') && ! RestaurantAccess::can(Auth::user(), 'order-logs') && ! in_array((int) Auth::user()->role_id, [1, 2], true)) {
            return redirect('admin')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        RestaurantTable::where('id', $id)->delete();

        return back()->with('message', 'Table removed.');
    }

    protected function canManage($write = false)
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        if (in_array((int) $user->role_id, [1, 2], true)) {
            return true;
        }
        if (RestaurantAccess::can($user, 'order-logs') || RestaurantAccess::can($user, 'tables-index')) {
            return true;
        }
        if ($write) {
            return RestaurantAccess::can($user, 'tables-add') || RestaurantAccess::can($user, 'tables-edit');
        }

        return RestaurantAccess::can($user, 'sales-add') || RestaurantAccess::can($user, 'waiter-orders');
    }
}
