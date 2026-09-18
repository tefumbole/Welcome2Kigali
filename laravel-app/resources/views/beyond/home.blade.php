@extends('beyond.layout')

@section('title', __('site.home.title'))
@section('meta_description', \App\Support\SiteContent::text('home.hero_subtitle', __('site.footer.blurb')))
@section('body_class', 'bg-black home-landing')
@section('landing_footer', '1')

@push('head')
<style>
    body.home-landing {
        background-color: #050505;
        height: 100dvh;
        overflow: hidden;
    }
    body.home-landing .landing-hero--fullpage {
        position: relative;
        background-color: #050505;
        background-image: none;
    }
    body.home-landing .landing-hero-art {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: contain;
        object-position: center;
        z-index: 1;
        pointer-events: none;
        user-select: none;
        -webkit-user-drag: none;
    }
    body.home-landing .landing-hero-actions {
        background: linear-gradient(to top, rgba(5,5,5,.45), transparent 70%);
        padding-bottom: 7.5rem;
    }
    @media (min-width: 640px) {
        body.home-landing .landing-hero-actions { padding-bottom: 8.25rem; }
    }
</style>
@endpush

@section('content')
<section class="landing-hero landing-hero--fullpage" aria-label="{{ \App\Support\SiteBrand::siteTitle($general_setting ?? null) }}">
    <h1 class="sr-only">{!! \App\Support\SiteContent::html('home.hero_title', \App\Support\SiteBrand::siteTitle($general_setting ?? null)) !!}</h1>
    <img class="landing-hero-art"
         src="{{ \App\Support\SiteBrand::landingUrl('2x') }}"
         srcset="{{ \App\Support\SiteBrand::landingUrl('1x') }} 1024w, {{ \App\Support\SiteBrand::landingUrl('2x') }} 2048w"
         sizes="100vw"
         width="2048"
         height="960"
         alt="{{ \App\Support\SiteBrand::siteTitle($general_setting ?? null) }}"
         decoding="async"
         fetchpriority="high">
    <div class="landing-hero-actions">
        <a href="{{ url('/register-now') }}"
           class="bg-brand-gold hover:bg-[#b08d45] text-black h-11 sm:h-12 px-6 sm:px-8 text-sm sm:text-base font-bold rounded-full hover:scale-105 transition-transform inline-flex items-center justify-center">
            {{ \App\Support\SiteContent::text('home.cta_primary', __('site.home.join')) }} <i data-lucide="arrow-right" class="ml-2 w-4 h-4"></i>
        </a>
        <a href="{{ url('/events') }}"
           class="h-11 sm:h-12 px-6 sm:px-8 text-sm sm:text-base font-bold rounded-full border border-brand-gold/80 text-brand-gold hover:bg-white/10 inline-flex items-center justify-center gap-2">
            <i data-lucide="calendar" class="w-4 h-4"></i> {{ \App\Support\SiteContent::text('home.cta_events', __('site.home.events')) }}
        </a>
        <a href="{{ url('/menu') }}"
           class="h-11 sm:h-12 px-6 sm:px-8 text-sm sm:text-base font-bold rounded-full border border-white/30 text-white hover:bg-white/10 inline-flex items-center justify-center gap-2">
            <i data-lucide="coffee" class="w-4 h-4"></i> {{ \App\Support\SiteContent::text('home.cta_cafe', __('site.home.cafe_menu')) }}
        </a>
    </div>
</section>
@endsection
