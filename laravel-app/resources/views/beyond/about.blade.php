@extends('beyond.layout')

@section('title', __('site.about.title'))
@section('meta_description', __('site.about.hero_sub'))

@section('content')
@php
    $visionHeading = \App\Support\SiteContent::text('about.vision_heading', __('site.about.vision'));
    $visionText = \App\Support\SiteContent::text('about.vision_text', __('site.about.vision_text'));
    $missionHeading = \App\Support\SiteContent::text('about.mission_heading', __('site.about.mission'));
    $missionText = \App\Support\SiteContent::text('about.mission_text', __('site.about.mission_text'));
    $aboutValues = [
        ['title' => __('site.about.v_hospitality'), 'text' => __('site.about.v_hospitality_d')],
        ['title' => __('site.about.v_urban'), 'text' => __('site.about.v_urban_d')],
        ['title' => __('site.about.v_premium'), 'text' => __('site.about.v_premium_d')],
        ['title' => __('site.about.v_intl'), 'text' => __('site.about.v_intl_d')],
        ['title' => __('site.about.v_exp'), 'text' => __('site.about.v_exp_d')],
    ];
@endphp

<section class="pt-8 pb-4 bg-brand-cream">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <p class="text-xs font-semibold tracking-[0.28em] uppercase text-brand-gold mb-2">{{ __('site.about.eyebrow') }}</p>
        <h1 class="font-serif text-3xl sm:text-4xl font-bold text-black mb-3">{{ \App\Support\SiteContent::text('about.hero_title', __('site.about.hero')) }}</h1>
        <p class="text-base text-gray-600 leading-relaxed">{{ \App\Support\SiteContent::text('about.hero_subtitle', __('site.about.hero_sub')) }}</p>
    </div>
</section>

<section class="pt-5 pb-8 bg-brand-cream">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-start">
            <div>
                <h2 class="font-serif text-2xl sm:text-3xl font-bold text-black mb-3">{{ $visionHeading }}</h2>
                <p class="text-base text-gray-600 leading-relaxed">{{ $visionText }}</p>
            </div>
            <div>
                <h2 class="font-serif text-2xl sm:text-3xl font-bold text-black mb-3">{{ $missionHeading }}</h2>
                <p class="text-base text-gray-600 leading-relaxed">{{ $missionText }}</p>
            </div>
        </div>
    </div>
</section>

<section class="pb-12 bg-brand-cream">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="font-serif text-2xl sm:text-3xl font-bold text-black mb-6 text-center">{{ \App\Support\SiteContent::text('about.values_heading', __('site.about.identity')) }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($aboutValues as $value)
                <div class="rounded-2xl border border-brand-gold/25 bg-white p-5 shadow-sm">
                    <h3 class="font-semibold text-black mb-2">{{ $value['title'] }}</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">{{ $value['text'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

@if(isset($leaders) && $leaders->count())
<section id="leadership" class="pt-2 pb-12 bg-brand-cream">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8">
            <h2 class="font-serif text-2xl sm:text-3xl md:text-4xl font-bold text-black mb-3">{{ \App\Support\SiteContent::text('about.leadership_heading', __('site.about.leadership')) }}</h2>
            <div class="h-1 w-24 bg-brand-gold mx-auto"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($leaders as $leader)
                <div class="group flex flex-col items-center text-center">
                    <div class="relative mb-4">
                        <div class="relative w-36 h-36 sm:w-44 sm:h-44 lg:w-48 lg:h-48 rounded-full p-[3px] bg-gradient-to-br from-brand-gold via-[#e0c07a] to-[#8a701f] shadow-[0_8px_24px_rgba(197,160,89,.28)]">
                            <div class="w-full h-full rounded-full overflow-hidden border-[3px] border-white bg-gray-100">
                                @if($leader->photoPublicUrl())
                                    <img src="{{ $leader->photoPublicUrl() }}" alt="{{ $leader->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gray-100 text-gray-400">
                                        <i data-lucide="user" class="w-16 h-16"></i>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-black">
                        {{ $leader->name }}
                        @if($leader->country)
                            <span class="ml-1" title="{{ $leader->country }}">{{ $leader->countryFlag() ?: '' }}</span>
                        @endif
                    </h3>
                    <p class="mt-1 text-sm font-semibold uppercase tracking-wide text-brand-gold">{{ $leader->title }}</p>
                    @if($leader->description)
                        <p class="mt-3 text-gray-600 text-sm leading-relaxed max-w-sm">{{ $leader->description }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection
