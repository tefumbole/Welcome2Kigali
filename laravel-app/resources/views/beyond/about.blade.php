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

    <section
        id="identity"
        class="pb-16"
        x-data="aboutIdentitySlides()"
        x-init="watch($el)"
    >
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-8">
                <h2 class="font-serif text-2xl sm:text-3xl font-bold text-black">{{ \App\Support\SiteContent::text('about.values_heading', __('site.about.identity')) }}</h2>
                <div class="h-1 w-16 bg-brand-gold mx-auto mt-3"></div>
            </div>
            <div class="relative">
                <div class="overflow-hidden rounded-2xl">
                    <div class="relative min-h-[13.5rem] sm:min-h-[12rem]">
                        @foreach($aboutValues as $index => $value)
                            <article
                                x-show="active === {{ $index }}"
                                x-transition:enter="transition ease-out duration-500"
                                x-transition:enter-start="opacity-0 translate-x-8"
                                x-transition:enter-end="opacity-100 translate-x-0"
                                x-transition:leave="transition ease-in duration-300"
                                x-transition:leave-start="opacity-100 translate-x-0"
                                x-transition:leave-end="opacity-0 -translate-x-8"
                                class="absolute inset-0 bg-white rounded-2xl border border-black/5 shadow-sm px-8 py-8 sm:px-10 sm:py-9 flex flex-col justify-center text-center"
                                @if($index > 0) x-cloak @endif
                            >
                                <h3 class="font-serif text-2xl sm:text-3xl font-bold text-black">{{ $value['title'] }}</h3>
                                <div class="h-1 w-10 bg-brand-gold mx-auto mt-3 mb-4"></div>
                                <p class="text-gray-600 leading-7 max-w-xl mx-auto">{{ $value['text'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
                <button type="button" class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-1 sm:-translate-x-4 w-10 h-10 rounded-full bg-white border border-black/10 shadow-sm text-black hover:text-brand-gold" @click="prev()" aria-label="Previous">
                    <span class="sr-only">Previous</span>
                    <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button type="button" class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-1 sm:translate-x-4 w-10 h-10 rounded-full bg-white border border-black/10 shadow-sm text-black hover:text-brand-gold" @click="next(); start()" aria-label="Next">
                    <span class="sr-only">Next</span>
                    <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
            <div class="flex justify-center gap-2 mt-6" role="tablist">
                @foreach($aboutValues as $index => $value)
                    <button
                        type="button"
                        class="w-2.5 h-2.5 rounded-full transition-colors"
                        :class="active === {{ $index }} ? 'bg-brand-gold' : 'bg-black/20'"
                        @click="go({{ $index }})"
                        :aria-selected="active === {{ $index }}"
                        aria-label="{{ $value['title'] }}"
                    ></button>
                @endforeach
            </div>
        </div>
    </section>
</div>

<script>
function aboutIdentitySlides() {
    return {
        active: 0,
        total: {{ count($aboutValues) }},
        timer: null,
        watch: function (el) {
            var self = this;
            if (!('IntersectionObserver' in window)) {
                self.start();
                return;
            }
            var seen = false;
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        if (!seen) {
                            seen = true;
                            self.active = 0;
                        }
                        self.start();
                    } else {
                        self.stop();
                    }
                });
            }, { threshold: 0.4 });
            observer.observe(el);
        },
        start: function () {
            var self = this;
            this.stop();
            this.timer = setInterval(function () { self.next(); }, 4200);
        },
        stop: function () {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },
        next: function () {
            this.active = (this.active + 1) % this.total;
        },
        prev: function () {
            this.active = (this.active - 1 + this.total) % this.total;
            this.start();
        },
        go: function (index) {
            this.active = index;
            this.start();
        }
    };
}
</script>
@endsection
