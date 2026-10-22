<?php

namespace App\Http\Controllers;

use App\Product;
use App\Support\AppVersion;
use App\Support\HelpTopics;
use App\Support\SiteBrand;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class HelpController extends Controller
{
    public function index()
    {
        $permissions = HelpTopics::userPermissionNames(Auth::user());
        $topics = HelpTopics::allowedTopics($permissions);
        $focus = HelpTopics::normalizeTopic(request('topic', request('tab')));

        if ($focus && ! isset($topics[$focus])) {
            abort(403, 'You are not allowed to view this Help topic.');
        }

        $products = collect();
        $grouped = collect();
        if (isset($topics['catalog']) && (! $focus || $focus === 'catalog') && Schema::hasTable('products')) {
            $products = Product::with(['category', 'unit', 'brand'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
            $grouped = $products->groupBy(function ($product) {
                return $product->category ? $product->category->name : 'Uncategorized';
            })->sortKeys();
        }

        return view('help.index', [
            'all_permission' => $permissions,
            'topics' => $topics,
            'focus' => $focus,
            'products' => $products,
            'grouped' => $grouped,
            'appVersion' => AppVersion::erp(),
            'appLogo' => SiteBrand::logoUrl(),
            'siteTitle' => optional(\App\GeneralSetting::latest()->first())->site_title ?: 'Welcome 2 Kigali',
        ]);
    }
}
