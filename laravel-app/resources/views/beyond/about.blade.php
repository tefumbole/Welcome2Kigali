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
    $leaders = isset($leaders) ? $leaders : collect();
@endphp

<div class="bg-brand-cream">
    <section class="pt-10 pb-4">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 text-center">
            <p class="text-xs font-semibold uppercase text-brand-gold tracking-[0.16em]">{{ __('site.about.eyebrow') }}</p>
            <h1 class="font-serif text-3xl sm:text-4xl font-bold text-black mt-2">{{ __('site.about.title') }}</h1>
        </div>
    </section>

    <section class="pt-4 pb-10">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <article class="bg-white rounded-2xl border border-black/5 shadow-sm p-7 sm:p-8">
                    <h2 class="font-serif text-2xl sm:text-3xl font-bold text-black">{{ $visionHeading }}</h2>
                    <div class="h-1 w-12 bg-brand-gold mt-3 mb-4"></div>
                    <p class="text-gray-600 leading-7">{{ $visionText }}</p>
                </article>
                <article class="bg-white rounded-2xl border border-black/5 shadow-sm p-7 sm:p-8">
                    <h2 class="font-serif text-2xl sm:text-3xl font-bold text-black">{{ $missionHeading }}</h2>
                    <div class="h-1 w-12 bg-brand-gold mt-3 mb-4"></div>
                    <p class="text-gray-600 leading-7">{{ $missionText }}</p>
                </article>
            </div>
        </div>
    </section>

    <section id="leadership" class="pb-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-3xl border border-black/5 shadow-sm px-6 py-10 sm:px-10 sm:py-12">
                <div class="text-center mb-10">
                    <h2 class="font-serif text-3xl sm:text-4xl font-bold text-black">{{ \App\Support\SiteContent::text('about.leadership_heading', __('site.about.leadership')) }}</h2>
                    <div class="h-1 w-16 bg-brand-gold mx-auto mt-3"></div>
                    <p class="mt-3 text-gray-500 text-sm sm:text-base">{{ __('site.about.leadership_sub') }}</p>
                </div>
                @if($leaders->count())
                    <div class="flex flex-wrap justify-center gap-x-14 gap-y-12">
                        @foreach($leaders as $leader)
                            <div class="flex flex-col items-center text-center w-full max-w-[18rem]">
                                <div class="w-48 h-48 sm:w-56 sm:h-56 rounded-full p-[5px] bg-gradient-to-br from-brand-gold via-[#e0c07a] to-[#8a701f] shadow-[0_16px_36px_rgba(10,10,10,.14)]">
                                    <div class="w-full h-full rounded-full overflow-hidden bg-gray-100 border-[4px] border-white">
                                        @if($leader->photoPublicUrl())
                                            <img src="{{ $leader->photoPublicUrl() }}" alt="{{ $leader->name ?: 'Leader' }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                <i data-lucide="user" class="w-16 h-16"></i>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @if(trim((string) $leader->name) !== '')
                                    <h3 class="mt-6 text-2xl font-bold text-black">
                                        {{ $leader->name }}
                                        @if($leader->country)
                                            <span class="ml-1" title="{{ $leader->country }}">{{ $leader->countryFlag() ?: '' }}</span>
                                        @endif
                                    </h3>
                                @endif
                                @if(trim((string) $leader->title) !== '')
                                    <p class="mt-1 text-sm font-semibold uppercase text-brand-gold tracking-[0.08em]">{{ $leader->title }}</p>
                                @endif
                                @if($leader->description)
                                    <p class="mt-3 text-gray-600 text-sm leading-relaxed">{{ $leader->description }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section id="identity" class="identity-fly pb-16">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-8">
                <h2 class="font-serif text-2xl sm:text-3xl font-bold text-black">{{ \App\Support\SiteContent::text('about.values_heading', __('site.about.identity')) }}</h2>
                <div class="h-1 w-16 bg-brand-gold mx-auto mt-3"></div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($aboutValues as $value)
                    <div class="identity-fly-card bg-white rounded-2xl border border-black/5 shadow-sm p-6">
                        <h3 class="font-semibold text-black mb-2">{{ $value['title'] }}</h3>
                        <p class="text-sm text-gray-600 leading-6">{{ $value['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</div>

<style>
    .identity-fly-card {
        opacity: 0;
        transform: translate3d(-56px, 36px, 0);
    }
    .identity-fly-card.is-in {
        opacity: 1;
        transform: none;
        transition: opacity 1.1s ease, transform 1.1s cubic-bezier(.16, 1, .3, 1);
    }
    @media (prefers-reduced-motion: reduce) {
        .identity-fly-card {
            opacity: 1;
            transform: none;
        }
    }
</style>
<noscript>
    <style>.identity-fly-card { opacity: 1; transform: none; }</style>
</noscript>
<script>
(function () {
    var section = document.getElementById('identity');
    if (!section) return;
    var cards = section.querySelectorAll('.identity-fly-card');
    var started = false;
    function reveal() {
        if (started) return;
        started = true;
        Array.prototype.forEach.call(cards, function (card, i) {
            setTimeout(function () { card.classList.add('is-in'); }, i * 1100);
        });
    }
    if (!('IntersectionObserver' in window)) {
        reveal();
        return;
    }
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                reveal();
                observer.disconnect();
            }
        });
    }, { threshold: 0.2 });
    observer.observe(section);
})();
</script>
@endsection
