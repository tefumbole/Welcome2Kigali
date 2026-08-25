@extends('beyond.layout')

@section('title', 'Expats Club')
@section('meta_description', 'Welcome 2 Kigali Expats Club — a destination, a community, an experience. Live. Connect. Thrive. in Kigali, Rwanda.')

@section('content')

@php
    $pillars = [
        ['icon' => 'music', 'title' => 'Live', 'desc' => 'Entertainment, dining, music, and the energy of urban Kigali.'],
        ['icon' => 'users', 'title' => 'Connect', 'desc' => 'Networking, community, and friendships across the expat world.'],
        ['icon' => 'trending-up', 'title' => 'Thrive', 'desc' => 'Wellness, lifestyle, opportunity, and elevated experiences.'],
    ];
    $spaces = [
        ['icon' => 'utensils', 'title' => 'Restaurant', 'desc' => 'Fine dining and everyday tables with Rwandan hospitality.'],
        ['icon' => 'wine', 'title' => 'Lounge', 'desc' => 'A refined space to meet, linger, and belong.'],
        ['icon' => 'calendar', 'title' => 'Event Space', 'desc' => 'Club nights, gatherings, and private celebrations.'],
        ['icon' => 'coffee', 'title' => 'Cafe', 'desc' => 'Crafted coffee, tea, juices, and our signature complimentary bite.'],
    ];
    $values = [
        ['icon' => 'heart', 'title' => 'Hospitality'],
        ['icon' => 'building-2', 'title' => 'Urban Culture'],
        ['icon' => 'gem', 'title' => 'Premium Lifestyle'],
        ['icon' => 'globe', 'title' => 'International Community'],
        ['icon' => 'map-pin', 'title' => 'Experiential Destination'],
    ];
    $contactEmail = \App\Support\SiteContent::text('contact.email', 'hello@welcome2kigali.com');
@endphp

{{-- Hero --}}
<section class="relative min-h-screen flex flex-col items-center justify-center overflow-hidden py-24 bg-black">
    <div class="absolute inset-0 bg-black">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_rgba(197,160,89,0.18),_transparent_60%)]"></div>
    </div>

    <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center w-full">
        <img src="{{ \App\Support\SiteBrand::logoUrl($general_setting ?? null) }}"
             alt="{{ \App\Support\SiteBrand::siteTitle($general_setting ?? null) }}"
             class="mx-auto h-48 md:h-72 w-auto object-contain drop-shadow-2xl mb-8">

        <p class="text-brand-gold text-xs md:text-sm tracking-[0.45em] uppercase mb-4">Expats Club · Experience Rwanda</p>
        <h1 class="font-serif text-3xl md:text-5xl lg:text-6xl font-bold text-white mb-4 tracking-wide">
            {!! \App\Support\SiteContent::html('home.hero_title', 'A destination. A community. An <span class="text-brand-gold">experience</span>.') !!}
        </h1>
        <p class="text-lg md:text-xl text-white/80 font-light max-w-3xl mx-auto">
            {{ \App\Support\SiteContent::text('home.hero_subtitle', 'Welcome 2 Kigali Expats Club — live, connect, and thrive in Rwanda.') }}
        </p>
        <p class="mt-6 text-white tracking-[0.35em] uppercase text-sm">Live. Connect. Thrive.</p>

        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4 flex-wrap">
            <a href="{{ url('/register-now') }}"
               class="bg-brand-gold hover:bg-[#b08d45] text-black h-14 px-8 text-lg font-bold rounded-full hover:scale-105 transition-transform inline-flex items-center justify-center">
                {{ \App\Support\SiteContent::text('home.cta_primary', 'Join the Club') }} <i data-lucide="arrow-right" class="ml-2 w-5 h-5"></i>
            </a>
            <a href="{{ url('/events') }}"
               class="h-14 px-8 text-lg font-bold rounded-full border border-brand-gold/80 text-brand-gold hover:bg-white/10 inline-flex items-center justify-center gap-2">
                <i data-lucide="calendar" class="w-5 h-5"></i> Events
            </a>
            <a href="{{ url('/menu') }}"
               class="h-14 px-8 text-lg font-bold rounded-full border border-white/30 text-white hover:bg-white/10 inline-flex items-center justify-center gap-2">
                <i data-lucide="coffee" class="w-5 h-5"></i> Cafe Menu
            </a>
        </div>
    </div>
</section>

{{-- Live / Connect / Thrive --}}
<section class="py-20 bg-black text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <h2 class="font-serif text-4xl font-bold text-brand-gold mb-3">{{ \App\Support\SiteContent::text('home.why_heading', 'Live. Connect. Thrive.') }}</h2>
            <p class="text-lg text-gray-300">{{ \App\Support\SiteContent::text('home.why_subheading', 'The pillars of the Welcome 2 Kigali experience') }}</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach ($pillars as $p)
                <div class="border border-brand-gold/30 rounded-xl p-8 text-center bg-white/5 hover:border-brand-gold transition">
                    <div class="text-brand-gold mb-4 flex justify-center"><i data-lucide="{{ $p['icon'] }}" class="w-12 h-12"></i></div>
                    <h3 class="font-serif text-2xl tracking-widest uppercase text-white mb-3">{{ $p['title'] }}</h3>
                    <p class="text-gray-300">{{ $p['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Spaces --}}
<section class="py-20 bg-brand-cream">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="font-serif text-4xl font-bold text-black mb-4">{{ \App\Support\SiteContent::text('home.services_heading', 'Restaurant. Lounge. Events. Cafe.') }}</h2>
            <p class="text-xl text-gray-600">{{ \App\Support\SiteContent::text('home.services_subheading', 'A premium hospitality destination for the international community in Kigali') }}</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach ($spaces as $s)
                <div class="bg-white rounded-xl p-8 border border-brand-gold/20 shadow-sm hover:shadow-lg transition">
                    <div class="text-brand-gold mb-4"><i data-lucide="{{ $s['icon'] }}" class="w-10 h-10"></i></div>
                    <h3 class="text-xl font-semibold text-black mb-2">{{ $s['title'] }}</h3>
                    <p class="text-gray-600 text-sm">{{ $s['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Values --}}
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="font-serif text-4xl font-bold text-black mb-4">{{ \App\Support\SiteContent::text('home.industries_heading', 'What we stand for') }}</h2>
            <p class="text-xl text-gray-600">{{ \App\Support\SiteContent::text('home.industries_subheading', 'Hospitality, urban culture, and a place to belong') }}</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-6">
            @foreach ($values as $v)
                <div class="text-center p-4">
                    <div class="text-brand-gold mb-3 flex justify-center"><i data-lucide="{{ $v['icon'] }}" class="w-9 h-9"></i></div>
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-black">{{ $v['title'] }}</h3>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Events --}}
@if(!empty($homeEvents) && $homeEvents->isNotEmpty())
<section class="py-16 bg-brand-cream border-t border-brand-gold/20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="font-serif text-4xl font-bold text-black mb-4">Upcoming Events</h2>
            <p class="text-xl text-gray-600">Gatherings at the club</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($homeEvents as $ev)
                <a href="{{ url('/events/' . $ev['slug']) }}" class="group block bg-white rounded-xl border border-gray-200 hover:border-brand-gold hover:shadow-xl transition overflow-hidden">
                    <div class="relative h-44 bg-gray-200 overflow-hidden">
                        @if(!empty($ev['flyer']))
                            <img src="{{ $ev['flyer'] }}" alt="{{ $ev['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-black">
                                <i data-lucide="calendar" class="w-12 h-12 text-brand-gold opacity-60"></i>
                            </div>
                        @endif
                        @if(!empty($ev['start']))
                            <span class="absolute top-3 right-3 bg-brand-gold text-black text-xs font-bold px-3 py-1 rounded-full">{{ $ev['start']->format('M d') }}</span>
                        @endif
                    </div>
                    <div class="p-5">
                        <h3 class="text-lg font-bold text-gray-900 group-hover:text-brand-gold line-clamp-2 mb-2">{{ $ev['title'] }}</h3>
                        @if(!empty($ev['start']))
                            <p class="text-sm text-gray-600 mb-1"><i data-lucide="calendar" class="w-4 h-4 inline text-brand-gold"></i> {{ $ev['start']->format('D, M j, Y g:i A') }}</p>
                        @endif
                        @if(!empty($ev['venue']))
                            <p class="text-sm text-gray-600 line-clamp-1"><i data-lucide="map-pin" class="w-4 h-4 inline text-brand-gold"></i> {{ $ev['venue'] }}</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
        <div class="text-center mt-10">
            <a href="{{ url('/events') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-black text-brand-gold font-semibold hover:bg-gray-900 transition">
                View all events <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>
    </div>
</section>
@endif

{{-- Cafe teaser --}}
<section class="py-16 bg-black text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="font-serif text-4xl font-bold text-brand-gold mb-4">{{ \App\Support\SiteContent::text('home.testimonials_heading', 'Cafe & Restaurant') }}</h2>
        <p class="text-xl text-gray-300 mb-4">{{ \App\Support\SiteContent::text('home.testimonials_subheading', 'Crafted beverages and food — dine in or take away') }}</p>
        <p class="text-gray-400 mb-8">Every beverage is served with our signature complimentary bite. Food dishes are added from the kitchen as products in the club menu.</p>
        <a href="{{ url('/menu') }}"
           class="inline-flex items-center gap-2 bg-brand-gold text-black font-bold text-lg px-8 py-4 rounded-full hover:scale-105 transition">
            <i data-lucide="coffee" class="w-5 h-5"></i> View the menu
        </a>
    </div>
</section>

{{-- CTA --}}
<section class="py-16 bg-brand-gold">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="font-serif text-4xl font-bold text-black mb-6">{{ \App\Support\SiteContent::text('home.cta_heading', 'Experience Rwanda. Belong in Kigali.') }}</h2>
        <p class="text-xl text-black/80 mb-8">{{ \App\Support\SiteContent::text('home.cta_text', 'Register for membership, join our events, or visit us at the club.') }}</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ url('/register-now') }}"
               class="px-8 py-4 text-lg rounded-lg bg-black text-brand-gold font-semibold inline-flex items-center justify-center gap-2">
                Register
            </a>
            <a href="mailto:{{ $contactEmail }}"
               class="bg-white text-black hover:bg-gray-100 px-8 py-4 text-lg rounded-lg font-semibold inline-flex items-center justify-center gap-2">
                <i data-lucide="mail" class="w-5 h-5"></i> Email Us
            </a>
        </div>
    </div>
</section>

@endsection
