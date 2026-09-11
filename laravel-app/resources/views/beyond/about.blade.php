@extends('beyond.layout')

@section('title', __('site.about.title'))
@section('meta_description', __('site.about.hero_sub'))

@section('content')
@include('beyond.partials.hero', [
    'title' => \App\Support\SiteContent::text('about.hero_title', __('site.about.hero')),
    'subtitle' => \App\Support\SiteContent::text('about.hero_subtitle', __('site.about.hero_sub')),
])
@php
    $visionHeading = \App\Support\SiteContent::text('about.vision_heading', __('site.about.vision'));
    $visionText = \App\Support\SiteContent::text('about.vision_text', __('site.about.vision_text'));
    $visionImage = \App\Support\SiteContent::image('about.vision_image');
    $missionHeading = \App\Support\SiteContent::text('about.mission_heading', __('site.about.mission'));
    $missionText = \App\Support\SiteContent::text('about.mission_text', __('site.about.mission_text'));
    $missionImage = \App\Support\SiteContent::image('about.about_image');
@endphp

<section class="py-16 bg-brand-cream">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid {{ $visionImage ? 'lg:grid-cols-2' : '' }} gap-10 lg:gap-16 items-center">
            @if($visionImage)
                <div class="order-1">
                    <div class="rounded-2xl overflow-hidden shadow-lg border border-black/5 bg-black aspect-[4/3]">
                        <img src="{{ $visionImage }}" alt="{{ $visionHeading }}" class="w-full h-full object-cover">
                    </div>
                </div>
            @endif
            <div class="{{ $visionImage ? 'order-2' : '' }}">
                <p class="text-xs font-semibold tracking-[0.28em] uppercase text-brand-gold mb-3">Welcome 2 Kigali</p>
                <h2 class="font-serif text-3xl sm:text-4xl font-bold text-black mb-6">{{ $visionHeading }}</h2>
                <p class="text-lg text-gray-600 leading-relaxed">{{ $visionText }}</p>
            </div>
        </div>
    </div>
</section>

<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid {{ $missionImage ? 'lg:grid-cols-2' : '' }} gap-10 lg:gap-16 items-center">
            <div class="{{ $missionImage ? 'order-2 lg:order-1' : '' }}">
                <h2 class="font-serif text-3xl sm:text-4xl font-bold text-black mb-6">{{ $missionHeading }}</h2>
                <p class="text-lg text-gray-600 leading-relaxed mb-8">{{ $missionText }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-2xl">
                    <div class="flex items-start gap-3">
                        <div class="bg-black p-2 rounded-lg"><i data-lucide="heart" class="w-6 h-6 text-brand-gold"></i></div>
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ __('site.about.hospitality') }}</h3>
                            <p class="text-sm text-gray-500">{{ __('site.about.hospitality_sub') }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="bg-black p-2 rounded-lg"><i data-lucide="globe-2" class="w-6 h-6 text-brand-gold"></i></div>
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ __('site.about.community') }}</h3>
                            <p class="text-sm text-gray-500">{{ __('site.about.community_sub') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @if($missionImage)
                <div class="order-1 lg:order-2">
                    <div class="rounded-2xl overflow-hidden shadow-lg border border-black/5 bg-black aspect-[4/3]">
                        <img src="{{ $missionImage }}" alt="{{ $missionHeading }}" class="w-full h-full object-cover">
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>

@if(isset($leaders) && $leaders->count())
<section id="leadership" class="py-20 bg-black">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="font-serif text-4xl font-bold text-white mb-4">{{ \App\Support\SiteContent::text('about.leadership_heading', __('site.about.leadership')) }}</h2>
            <div class="h-1 w-24 bg-brand-gold mx-auto"></div>
            <p class="mt-4 text-xl text-gray-300">{{ \App\Support\SiteContent::text('about.leadership_subtext', __('site.about.leadership_sub')) }}</p>
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
        <h2 class="font-serif text-3xl font-bold text-black mb-12">{{ \App\Support\SiteContent::text('about.values_heading', __('site.about.identity')) }}</h2>
        <div class="grid md:grid-cols-3 lg:grid-cols-5 gap-6">
            @foreach ([
                ['utensils-crossed', __('site.about.v_hospitality'), __('site.about.v_hospitality_d')],
                ['building-2', __('site.about.v_urban'), __('site.about.v_urban_d')],
                ['gem', __('site.about.v_premium'), __('site.about.v_premium_d')],
                ['users', __('site.about.v_intl'), __('site.about.v_intl_d')],
                ['map-pin', __('site.about.v_exp'), __('site.about.v_exp_d')],
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

<section class="py-16 bg-brand-cream">
    <div class="max-w-3xl mx-auto px-4 text-center">
        <h2 class="font-serif text-3xl font-bold text-black mb-3">{{ \App\Support\SiteContent::text('about.cta_heading', 'Ready to belong in Kigali?') }}</h2>
        <p class="text-gray-600 mb-6">{{ \App\Support\SiteContent::text('about.cta_text', 'Join the club, come to an event, or visit the cafe.') }}</p>
        <a href="{{ url('/register-now') }}" class="inline-flex items-center px-6 py-3 rounded-full bg-black text-brand-gold font-semibold">{{ \App\Support\SiteContent::text('home.cta_primary', __('site.home.join')) }}</a>
    </div>
</section>

@include('beyond.partials.contact_section')

@endsection
