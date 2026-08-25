@extends('beyond.layout')

@section('title', 'About the Club')
@section('meta_description', 'Welcome 2 Kigali Expats Club is more than a logo. It is the visual identity of a destination experience in Kigali, Rwanda.')

@section('content')

<section class="relative py-24 bg-black text-white overflow-hidden">
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_rgba(197,160,89,0.16),_transparent_65%)]"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <p class="text-brand-gold text-xs tracking-[0.4em] uppercase mb-4">Welcome 2 Kigali · Expats Club</p>
        <h1 class="font-serif text-4xl md:text-6xl font-bold mb-6">{{ \App\Support\SiteContent::text('about.hero_title', 'More than a logo. A destination experience.') }}</h1>
        <p class="text-xl md:text-2xl text-gray-200 max-w-3xl mx-auto font-light">
            {{ \App\Support\SiteContent::text('about.hero_subtitle', 'A place where people arrive, connect, discover Rwanda, experience culture, build relationships, and create memories.') }}
        </p>
        <p class="mt-8 text-brand-gold tracking-[0.35em] uppercase text-sm">Live. Connect. Thrive.</p>
    </div>
</section>

<section class="py-16 bg-brand-cream">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="font-serif text-3xl font-bold text-black mb-6">{{ \App\Support\SiteContent::text('about.mission_heading', 'Our Mission') }}</h2>
                <p class="text-lg text-gray-600 mb-8 leading-relaxed">
                    {{ \App\Support\SiteContent::text('about.mission_text', 'To welcome the international community into Kigali with world-class hospitality, authentic Rwandan culture, and a club where people live, connect, and thrive.') }}
                </p>
                <div class="grid grid-cols-2 gap-6">
                    <div class="flex items-start gap-3">
                        <div class="bg-black p-2 rounded-lg"><i data-lucide="heart" class="w-6 h-6 text-brand-gold"></i></div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Hospitality</h3>
                            <p class="text-sm text-gray-500">Warm, world-class service</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="bg-black p-2 rounded-lg"><i data-lucide="globe-2" class="w-6 h-6 text-brand-gold"></i></div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Community</h3>
                            <p class="text-sm text-gray-500">International, membership-oriented</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="relative rounded-2xl overflow-hidden bg-black flex items-center justify-center p-8">
                <img src="{{ \App\Support\SiteContent::image('about.about_image', '/branding/w2k-logo.png') }}" alt="Welcome 2 Kigali" class="w-full max-h-96 object-contain">
            </div>
        </div>
    </div>
</section>

@if(isset($leaders) && $leaders->count())
<section id="leadership" class="py-20 bg-black">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="font-serif text-4xl font-bold text-white mb-4">{{ \App\Support\SiteContent::text('about.leadership_heading', 'Our Leadership') }}</h2>
            <div class="h-1 w-24 bg-brand-gold mx-auto"></div>
            <p class="mt-4 text-xl text-gray-300">{{ \App\Support\SiteContent::text('about.leadership_subtext', 'The people hosting Welcome 2 Kigali') }}</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
            @foreach($leaders as $leader)
                <div class="group flex flex-col items-center text-center">
                    <div class="relative mb-6">
                        <div class="relative w-48 h-48 rounded-full p-1 bg-gradient-to-br from-brand-gold to-[#8a701f]">
                            <div class="w-full h-full rounded-full overflow-hidden border-4 border-black bg-gray-200">
                                @if($leader->photoPublicUrl())
                                    <img src="{{ $leader->photoPublicUrl() }}" alt="{{ $leader->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gray-200 text-gray-400">
                                        <i data-lucide="user" class="w-16 h-16"></i>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-white">
                        {{ $leader->name }}
                        @if($leader->country)
                            <span class="ml-1" title="{{ $leader->country }}">{{ $leader->countryFlag() ?: '' }}</span>
                        @endif
                    </h3>
                    <p class="mt-1 text-sm font-semibold uppercase tracking-wide text-brand-gold">{{ $leader->title }}</p>
                    @if($leader->description)
                        <p class="mt-3 text-gray-300 text-sm leading-relaxed max-w-sm">{{ $leader->description }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="font-serif text-3xl font-bold text-black mb-12">{{ \App\Support\SiteContent::text('about.values_heading', 'The identity combines') }}</h2>
        <div class="grid md:grid-cols-3 lg:grid-cols-5 gap-6">
            @foreach ([
                ['utensils-crossed', 'Hospitality', 'Warm, welcoming experiences rooted in world-class service and authentic Rwandan hospitality.'],
                ['building-2', 'Urban Culture', 'A vibrant fusion of modern city life, creativity, music, art, and contemporary African culture.'],
                ['gem', 'Premium Lifestyle', 'Elevated experiences curated for those who appreciate quality, comfort, and exclusivity.'],
                ['users', 'International Community', 'A diverse community of global minds coming together, sharing, growing, and belonging.'],
                ['map-pin', 'Experiential Destination', 'Positioning Kigali as a must-experience destination through immersive, memorable experiences.'],
            ] as [$icon, $title, $desc])
                <div class="p-6 bg-brand-cream rounded-xl hover:shadow-lg transition-shadow">
                    <i data-lucide="{{ $icon }}" class="w-10 h-10 text-brand-gold mx-auto mb-4"></i>
                    <h3 class="text-lg font-bold text-black mb-2">{{ $title }}</h3>
                    <p class="text-gray-600 text-sm">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="py-16 bg-black text-white text-center">
    <div class="max-w-4xl mx-auto px-4">
        <h2 class="font-serif text-3xl font-bold mb-6">{{ \App\Support\SiteContent::text('about.cta_heading', 'Ready to belong in Kigali?') }}</h2>
        <p class="text-xl mb-8 text-gray-300">{{ \App\Support\SiteContent::text('about.cta_text', 'Join the club, come to an event, or visit the cafe.') }}</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ url('/register-now') }}"
               class="inline-flex items-center gap-2 bg-brand-gold text-black font-bold text-lg px-8 py-4 rounded-full hover:scale-105 transition-all">
                Register
            </a>
            <a href="{{ url('/menu') }}"
               class="inline-flex items-center gap-2 border border-brand-gold text-brand-gold font-bold text-lg px-8 py-4 rounded-full hover:bg-white/5 transition-all">
                View the menu
            </a>
        </div>
    </div>
</section>

@include('beyond.partials.contact_section')

@endsection
