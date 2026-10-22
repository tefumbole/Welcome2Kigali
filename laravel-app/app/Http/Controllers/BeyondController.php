<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;

class BeyondController extends Controller
{
    public function home()
    {
        $pubService = app(\App\Services\EventPublicationService::class);
        $homeEvents = $pubService->publishedQuery()
            ->orderBy('event_start_at')
            ->limit(6)
            ->get()
            ->map(function ($event) use ($pubService) {
                $pub = $event->publication;

                return [
                    'slug' => $event->slug,
                    'title' => $pub->public_title ?: $event->name,
                    'summary' => $pub->public_summary,
                    'flyer' => $pubService->publicFlyerUrl($event, $pub),
                    'start' => $event->event_start_at,
                    'venue' => $pub->public_venue ?: $event->venue,
                    'status' => $pubService->computePublicStatus($event, $pub),
                ];
            });

        return view('beyond.home', ['homeEvents' => $homeEvents]);
    }

    public function about()
    {
        $leaders = \App\Leader::published()->ordered()->get();
        if ($leaders->isEmpty() && \Illuminate\Support\Facades\Schema::hasTable('leaders')) {
            $leaders = \App\Leader::ordered()->get();
        }

        return view('beyond.about', [
            'leaders' => $leaders,
        ]);
    }

    public function services()
    {
        return view('beyond.services', [
            'services' => $this->servicesList(),
        ]);
    }

    public function serviceShow($service, $category = null)
    {
        if (! \App\Support\ServiceCatalog::exists($service)) {
            abort(404);
        }
        $catalog = \App\Support\ServiceCatalog::class;
        $tree = $catalog::categories($service);
        $direct = request()->boolean('direct');
        $current = null;
        $chips = collect();
        $items = collect();

        if ($category !== null) {
            $current = $catalog::findInTree($tree, (int) $category);
            if (! $current) {
                return redirect()->route('beyond.services.show', $service);
            }
            if ($direct || empty($current['has_children'])) {
                // A category with no subcategories opens straight onto its items.
                $items = $catalog::items($current['id'], ! $direct);
            } else {
                $chips = collect($current['children'] ?? [])->values();
                if ((int) ($current['own_count'] ?? 0) > 0) {
                    $self = $current;
                    $self['items_only'] = true;
                    $self['has_children'] = false;
                    $self['count'] = (int) $current['own_count'];
                    $chips->prepend($self);
                }
            }
        } else {
            $nodes = $catalog::browseNodes($tree);
            if ($nodes->count() === 1 && empty($nodes[0]['has_children'])) {
                $current = $nodes[0];
                $items = $catalog::items($current['id'], empty($current['items_only']));
            } else {
                $chips = $nodes;
            }
        }

        $breadcrumb = $current ? $catalog::breadcrumb($tree, $current['id']) : [];

        $cartCount = 0;
        foreach (session('cart', []) as $row) {
            $cartCount += (int) ($row['quantity'] ?? 0);
        }

        return view('beyond.service-show', [
            'service' => $service,
            'meta' => $catalog::SERVICES[$service],
            'categories' => $chips,
            'parents' => $tree,
            'current' => $current,
            'subcategories' => $chips,
            'chips' => $chips,
            'breadcrumb' => $breadcrumb,
            'items' => $items,
            'cartCount' => $cartCount,
        ]);
    }

    public function projects()
    {
        return view('beyond.projects', [
            'projects' => [
                [
                    'url' => 'https://www.tiktok.com/@tefurolandmbole/video/7495818139272301829',
                    'title' => 'Project Highlight: Professional Installation',
                ],
                [
                    'url' => 'https://www.tiktok.com/@tefurolandmbole/video/7493245944540974341',
                    'title' => 'Advanced Networking Setup',
                ],
                [
                    'url' => 'https://www.tiktok.com/@tefurolandmbole/video/7492891748327361797',
                    'title' => 'Audio-Visual Excellence',
                ],
            ],
        ]);
    }

    public function gallery()
    {
        return view('beyond.gallery', [
            'items' => \App\GalleryItem::published()->ordered()->get(),
        ]);
    }

    public function contact()
    {
        return redirect(url('/about') . '#contact', 301);
    }

    public function storeContact(\Illuminate\Http\Request $request)
    {
        return $this->contactCompose($request);
    }

    public function contactCompose(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'nullable|string|max:40',
            'subject' => 'required|string|max:190',
            'message' => 'required|string|max:5000',
        ]);

        $result = app(\App\Services\ContactInquiryService::class)->submit($data, $request->ip());

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'serial' => $result['serial'],
                'message' => 'Thank you. Your message '.$result['serial'].' has been received.',
            ]);
        }

        return back()->with('message', 'Thank you. Your message '.$result['serial'].' has been received.');
    }

    public function menu()
    {
        $cart = session('cart', []);
        $cartCount = 0;
        foreach ($cart as $row) {
            $cartCount += (int) ($row['quantity'] ?? 0);
        }

        return view('beyond.menu', [
            'groups' => $this->menuGroups(),
            'cartCount' => $cartCount,
        ]);
    }

    public function menuQr()
    {
        return view('beyond.menu-qr', [
            'qr' => \App\Support\CafeMenuQr::dataUri(720),
            'menuUrl' => \App\Support\CafeMenuQr::menuUrl(),
        ]);
    }

    public function menuQrPng()
    {
        $png = \App\Support\CafeMenuQr::pngBinary(720);
        if ($png === '') {
            abort(500, 'Could not build menu QR.');
        }

        return response($png, 200)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'inline; filename="w2k-menu-qr.png"')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function menuData()
    {
        return response()->json(['groups' => $this->menuGroups()]);
    }

    private function menuGroups()
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('categories') || ! \Illuminate\Support\Facades\Schema::hasTable('products')) {
            return [];
        }

        if (\App\Support\ServiceCatalog::enabled()) {
            $groups = \App\Support\ServiceCatalog::menuGroups();
            if ($groups->isNotEmpty()) {
                return $groups->map(function ($group) {
                    $look = $this->menuSectionLook($group['name']);
                    $group['tagline'] = $look['tagline'];
                    $group['blurb'] = $look['blurb'];
                    if (empty($group['image'])) {
                        $group['image'] = $look['image'];
                    }
                    // Soft buckets for classic cafe names when no DB children exist.
                    if (empty($group['has_children'])) {
                        $group['buckets'] = $this->menuSoftBuckets($group['name'], $group['items']);
                    }

                    return $group;
                })->values();
            }
        }

        $categoryNames = [
            'Coffee & Tea',
            'Iced & Specialty',
            'Tea & Hot Beverages',
            'Fresh & Detox Juices',
            'Smoothies',
            'Food',
        ];

        $categories = $this->cafeCategories($categoryNames);
        $productCount = $categories->isEmpty()
            ? 0
            : \App\Product::whereIn('category_id', $categories->pluck('id'))->count();
        if ($categories->isEmpty() || $productCount < 1) {
            $this->ensureCafeMenu();
            $categories = $this->cafeCategories($categoryNames);
        }

        if ($categories->isEmpty()) {
            $categories = \App\Category::orderBy('name')->get();
        }

        return $categories->map(function ($category) {
            $items = \App\Product::where('category_id', $category->id)
                ->where('is_active', true)
                ->orderBy('id')
                ->get()
                ->map(function ($product) {
                    $flavors = [];
                    if (stripos($product->name, 'Flavored Tea') !== false) {
                        $flavors = ['Cinnamon', 'Earl Grey', 'Chamomile', 'Masala', 'Clover', 'Peppermint', 'Chai', 'Hibiscus'];
                    }

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'details' => $product->product_details,
                        'image' => $this->menuProductImage($product->image),
                        'icon' => \App\Support\ServiceCatalog::categoryIcon($product->name),
                        'price' => (int) $product->price,
                        'flavors' => $flavors,
                    ];
                });

            $look = $this->menuSectionLook($category->name);

            return [
                'id' => $category->id,
                'name' => $category->name,
                'icon' => \App\Support\ServiceCatalog::categoryIcon($category->name),
                'tagline' => $look['tagline'],
                'blurb' => $look['blurb'],
                'image' => $this->menuCategoryImage($category, $look['image']),
                'items' => $items,
                'buckets' => $this->menuSoftBuckets($category->name, $items->all()),
                'has_children' => false,
            ];
        })->values();
    }

    private function menuSoftBuckets($name, $items)
    {
        $items = collect($items)->values()->all();
        if ($name === 'Iced & Specialty') {
            $iced = [];
            $specialty = [];
            foreach ($items as $bucketItem) {
                if (strpos($bucketItem['name'], 'Ice ') === 0) {
                    $iced[] = $bucketItem;
                } else {
                    $specialty[] = $bucketItem;
                }
            }

            return [
                ['title' => 'Iced Coffees', 'icon' => 'glass-water', 'items' => $iced],
                ['title' => 'Specialty Coffees / Brewing', 'icon' => 'coffee', 'items' => $specialty],
            ];
        }
        if ($name === 'Coffee & Tea') {
            return [['title' => 'Espresso Beverages', 'icon' => 'coffee', 'items' => $items]];
        }
        if ($name === 'Fresh & Detox Juices') {
            $fresh = [];
            $detox = [];
            foreach ($items as $bucketItem) {
                if (stripos($bucketItem['name'], 'Detox') !== false) {
                    $detox[] = $bucketItem;
                } else {
                    $fresh[] = $bucketItem;
                }
            }

            return [
                ['title' => 'Fresh Juices', 'icon' => 'citrus', 'items' => $fresh],
                ['title' => 'Detox Juices', 'icon' => 'sparkles', 'items' => $detox],
            ];
        }

        return [['title' => $name, 'icon' => \App\Support\ServiceCatalog::categoryIcon($name), 'items' => $items]];
    }

    private function cafeCategories(array $categoryNames)
    {
        $categories = \App\Category::whereIn('name', $categoryNames)->get();
        $inactiveIds = $categories->filter(function ($category) {
            return ! $category->is_active;
        })->pluck('id');
        if ($inactiveIds->count()) {
            \App\Category::whereIn('id', $inactiveIds)->update(['is_active' => 1]);
            $categories = \App\Category::whereIn('name', $categoryNames)->get();
        }

        return $categories->sortBy(function ($category) use ($categoryNames) {
            $index = array_search($category->name, $categoryNames, true);

            return $index === false ? 999 : $index;
        })->values();
    }

    private function ensureCafeMenu()
    {
        try {
            (new \CafeMenuSeeder())->run();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Cafe menu seed failed: '.$e->getMessage());
        }
    }

    private function menuProductImage($filename)
    {
        return \App\Support\PosProductImage::photoUrl($filename) ?: '';
    }

    private function menuCategoryImage($category, $fallback)
    {
        $fromCatalog = \App\Support\ServiceCatalog::categoryImage($category);
        if ($fromCatalog) {
            return $fromCatalog;
        }

        return $fallback;
    }

    private function menuSectionLook($name)
    {
        $map = [
            'Coffee & Tea' => [
                'tagline' => 'Crafted to perfection.',
                'blurb' => '',
                'image' => url('public/branding/menu/coffee-tea.jpg').'?v=3',
            ],
            'Iced & Specialty' => [
                'tagline' => 'Bold. Smooth. Refreshing.',
                'blurb' => '',
                'image' => url('public/branding/menu/iced-specialty.jpg').'?v=3',
            ],
            'Tea & Hot Beverages' => [
                'tagline' => 'Warmth in every cup.',
                'blurb' => '',
                'image' => url('public/branding/menu/tea-hot.jpg').'?v=3',
            ],
            'Fresh & Detox Juices' => [
                'tagline' => 'Pure. Vibrant. Nourishing.',
                'blurb' => 'Made fresh daily with the finest ingredients to uplift, energize and refresh your day.',
                'image' => url('public/branding/menu/smoothies.jpg').'?v=3',
            ],
            'Smoothies' => [
                'tagline' => 'Creamy. Fruity. Nutritious.',
                'blurb' => 'Blended to perfection with real fruits and quality ingredients for a deliciously healthy treat.',
                'image' => url('public/branding/menu/smoothies.jpg').'?v=3',
            ],
            'Food' => [
                'tagline' => 'Coming to the table soon.',
                'blurb' => '',
                'image' => url('public/branding/menu/coffee-tea.jpg').'?v=3',
            ],
        ];

        return $map[$name] ?? [
            'tagline' => 'Live. Connect. Thrive.',
            'blurb' => '',
            'image' => url('public/branding/menu/coffee-tea.jpg').'?v=3',
        ];
    }

    public function events()
    {
        return view('beyond.events', ['events' => []]);
    }

    private function servicesList()
    {
        return [
            ['emoji' => '🤖', 'title' => 'Artificial Intelligence', 'description' => 'Cutting-edge AI solutions for business automation and intelligent decision-making'],
            ['emoji' => '☁️', 'title' => 'Cloud Computing', 'description' => 'Scalable cloud infrastructure and migration services for modern enterprises'],
            ['emoji' => '🔒', 'title' => 'Cyber Security', 'description' => 'Comprehensive security solutions to protect your digital assets and data'],
            ['emoji' => '💼', 'title' => 'General IT Consultancy', 'description' => 'Expert IT guidance and strategic consulting for digital transformation'],
            ['emoji' => '📞', 'title' => 'VoIP', 'description' => 'Reliable voice over IP solutions for seamless business communication'],
            ['emoji' => '🌐', 'title' => 'Network Infrastructure Design', 'description' => 'Robust network architecture and infrastructure planning for optimal performance'],
            ['emoji' => '📹', 'title' => 'CCTV and More', 'description' => 'Advanced surveillance and security systems for comprehensive monitoring'],
        ];
    }
}
