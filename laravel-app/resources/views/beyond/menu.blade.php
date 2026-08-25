@extends('beyond.layout')

@section('title', 'Cafe & Restaurant Menu')
@section('meta_description', 'Welcome 2 Kigali cafe and restaurant menu. Coffee, tea, juices, smoothies, and food. Prices in FRW, tax included.')

@section('content')

<section class="relative py-20 bg-black text-white overflow-hidden">
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_rgba(197,160,89,0.16),_transparent_65%)]"></div>
    <div class="relative max-w-4xl mx-auto px-4 text-center">
        <p class="text-brand-gold text-xs tracking-[0.4em] uppercase mb-4">Live. Connect. Thrive.</p>
        <h1 class="font-serif text-4xl md:text-6xl font-bold mb-4">
            {!! \App\Support\SiteContent::html('menu.hero_title', 'Cafe & <span class="text-brand-gold">Restaurant</span>') !!}
        </h1>
        <p class="text-lg text-gray-300 max-w-2xl mx-auto">
            {{ \App\Support\SiteContent::text('menu.hero_subtitle', 'Crafted to perfection. Every beverage is served with our signature complimentary bite. Prices in FRW, tax included.') }}
        </p>
    </div>
</section>

<section class="py-16 bg-brand-cream">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        @if (empty($groups) || count($groups) === 0)
            <div class="bg-white border border-brand-gold/30 rounded-xl p-10 text-center">
                <p class="text-gray-700 mb-4">The menu will appear here after cafe products are seeded in the Welcome 2 Kigali database.</p>
                <p class="text-sm text-gray-500">Admin → Product to add food dishes. Then run <code>php artisan db:seed --class=CafeMenuSeeder</code> locally on the isolated database.</p>
            </div>
        @else
            @foreach ($groups as $group)
                <div class="mb-14">
                    <div class="flex items-center gap-4 mb-6">
                        <h2 class="font-serif text-3xl font-bold text-black">{{ $group['name'] }}</h2>
                        <div class="flex-1 h-px bg-brand-gold/40"></div>
                    </div>
                    @if (count($group['items']) === 0)
                        <p class="text-gray-500 italic">Food dishes will be listed here as they are added in admin → Product.</p>
                    @else
                        <div class="bg-white rounded-xl border border-brand-gold/20 overflow-hidden">
                            @foreach ($group['items'] as $item)
                                <div class="flex items-start justify-between gap-4 px-6 py-4 border-b border-gray-100 last:border-0">
                                    <div>
                                        <h3 class="font-semibold text-black">{{ $item['name'] }}</h3>
                                        @if (!empty($item['details']))
                                            <p class="text-sm text-gray-500">{{ $item['details'] }}</p>
                                        @endif
                                    </div>
                                    <p class="text-brand-gold font-semibold whitespace-nowrap">{{ number_format($item['price']) }} <span class="text-xs tracking-wide">FRW</span></p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        @endif

        <div class="mt-8 grid sm:grid-cols-2 gap-4">
            <div class="border border-brand-gold/40 rounded-xl p-6 bg-white">
                <p class="text-brand-gold text-xs tracking-widest uppercase mb-2">Dine in</p>
                <p class="text-gray-700">Elegantly served. Thoughtfully paired.</p>
            </div>
            <div class="border border-brand-gold/40 rounded-xl p-6 bg-white">
                <p class="text-brand-gold text-xs tracking-widest uppercase mb-2">Take away</p>
                <p class="text-gray-700">Beautifully packed. Always with your bite.</p>
            </div>
        </div>
        <p class="text-center text-gray-500 text-sm mt-8">Prices are in FRW | Tax included</p>
    </div>
</section>

@endsection
