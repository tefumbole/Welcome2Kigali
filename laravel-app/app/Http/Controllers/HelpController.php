<?php

namespace App\Http\Controllers;

use App\Product;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class HelpController extends Controller
{
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        $all_permission = [];
        if ($role) {
            foreach ($role->permissions as $permission) {
                $all_permission[] = $permission->name;
            }
        }

        $products = Product::with(['category', 'unit', 'brand'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $grouped = $products->groupBy(function ($product) {
            return $product->category ? $product->category->name : 'Uncategorized';
        })->sortKeys();

        return view('help.index', [
            'all_permission' => $all_permission,
            'products' => $products,
            'grouped' => $grouped,
            'tab' => request('tab', 'guide'),
        ]);
    }
}
