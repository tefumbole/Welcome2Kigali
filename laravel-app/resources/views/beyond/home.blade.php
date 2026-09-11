@extends('beyond.layout')

@section('title', __('site.home.title'))
@section('meta_description', \App\Support\SiteContent::text('home.hero_subtitle', __('site.footer.blurb')))
@section('body_class', 'bg-black home-landing')
@section('hide_footer', '1')

@push('head')
<style>
    body.home-landing {
        background-color: #0A0A0A;
        height: 100dvh;
        overflow: hidden;
    }
    body.home-landing .landing-hero--fullpage {
        background-image: url('{{ \App\Support\SiteBrand::landingUrl() }}');
        background-size: contain;
        background-position: center center;
        background-repeat: no-repeat;
        background-color: #0A0A0A;
    }
</style>
@endpush

@section('content')
<section class="landing-hero landing-hero--fullpage" aria-label="{{ \App\Support\SiteBrand::siteTitle($general_setting ?? null) }}">
    <h1 class="sr-only">{!! \App\Support\SiteContent::html('home.hero_title', \App\Support\SiteBrand::siteTitle($general_setting ?? null)) !!}</h1>
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
