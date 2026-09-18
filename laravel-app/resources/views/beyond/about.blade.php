@extends('beyond.layout')

@section('title', __('site.about.title'))
@section('meta_description', __('site.about.hero_sub'))

@section('content')
@php
    $visionHeading = \App\Support\SiteContent::text('about.vision_heading', __('site.about.vision'));
    $visionText = \App\Support\SiteContent::text('about.vision_text', __('site.about.vision_text'));
    $missionHeading = \App\Support\SiteContent::text('about.mission_heading', __('site.about.mission'));
    $missionText = \App\Support\SiteContent::text('about.mission_text', __('site.about.mission_text'));
@endphp

<section class="pt-5 pb-8 bg-brand-cream">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-start">
            <div>
                <p class="text-xs font-semibold tracking-[0.28em] uppercase text-brand-gold mb-2">Welcome 2 Kigali</p>
                <h2 class="font-serif text-3xl font-bold text-black mb-3">{{ $visionHeading }}</h2>
                <p class="text-base text-gray-600 leading-relaxed">{{ $visionText }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold tracking-[0.28em] uppercase text-transparent mb-2 select-none" aria-hidden="true">&nbsp;</p>
                <h2 class="font-serif text-3xl font-bold text-black mb-3">{{ $missionHeading }}</h2>
                <p class="text-base text-gray-600 leading-relaxed">{{ $missionText }}</p>
            </div>
        </div>
    </div>
</section>

@if(isset($leaders) && $leaders->count())
<section id="leadership" class="pt-6 pb-10 bg-black">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-6">
            <h2 class="font-serif text-4xl font-bold text-white mb-3">{{ \App\Support\SiteContent::text('about.leadership_heading', __('site.about.leadership')) }}</h2>
            <div class="h-1 w-24 bg-brand-gold mx-auto"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($leaders as $leader)
                <div class="group flex flex-col items-center text-center">
                    <div class="relative mb-4">
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

<section class="py-10 bg-brand-cream">
    <div class="max-w-3xl mx-auto px-4 text-center">
        <a href="{{ url('/register-now') }}" class="inline-flex items-center px-6 py-3 rounded-full bg-black text-brand-gold font-semibold">{{ \App\Support\SiteContent::text('home.cta_primary', __('site.home.join')) }}</a>
    </div>
</section>

@include('beyond.partials.contact_section')

@endsection
