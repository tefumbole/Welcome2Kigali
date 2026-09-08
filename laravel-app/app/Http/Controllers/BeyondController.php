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
        return view('beyond.about', [
            'leaders' => \App\Leader::published()->ordered()->get(),
        ]);
    }

    public function services()
    {
        return view('beyond.services', [
            'services' => $this->servicesList(),
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

        $categoryNames = [
            'Coffee & Tea',
            'Iced & Specialty',
            'Tea & Hot Beverages',
            'Fresh & Detox Juices',
            'Smoothies',
            'Food',
        ];

        $categories = \App\Category::where('is_active', 1)
            ->whereIn('name', $categoryNames)
            ->orderByRaw('FIELD(name, "'.implode('","', $categoryNames).'")')
            ->get();

        if ($categories->isEmpty()) {
            $categories = \App\Category::where('is_active', 1)->orderBy('name')->get();
        }

        return $categories->map(function ($category) {
            $items = \App\Product::where('category_id', $category->id)
                ->where('is_active', 1)
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
                        'price' => (int) $product->price,
                        'flavors' => $flavors,
                    ];
                });

            $look = $this->menuSectionLook($category->name);

            return [
                'id' => $category->id,
                'name' => $category->name,
                'tagline' => $look['tagline'],
                'blurb' => $look['blurb'],
                'image' => $look['image'],
                'items' => $items,
            ];
        })->values();
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
