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
        return view('beyond.menu', ['groups' => $this->menuGroups()]);
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
                ->orderBy('name')
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'details' => $product->product_details,
                        'price' => (int) $product->price,
                    ];
                });

            return [
                'id' => $category->id,
                'name' => $category->name,
                'items' => $items,
            ];
        })->values();
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
