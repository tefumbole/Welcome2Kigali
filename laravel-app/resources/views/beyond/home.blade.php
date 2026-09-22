@extends('beyond.layout')

@section('title', __('site.home.title'))
@section('meta_description', \App\Support\SiteContent::text('home.hero_subtitle', __('site.footer.blurb')))
@section('body_class', 'bg-black home-landing')
@section('hide_footer', '1')

@push('head')
<link rel="preload" as="image"
      href="{{ \App\Support\SiteBrand::landingUrl('2x') }}"
      imagesrcset="{{ \App\Support\SiteBrand::landingUrl('1x') }} 1280w, {{ \App\Support\SiteBrand::landingUrl('2x') }} 1678w, {{ \App\Support\SiteBrand::landingUrl('png') }} 1678w"
      imagesizes="100vw"
      fetchpriority="high">
<style>
    body.home-landing {
        background-color: #0A0A0A;
        height: 100dvh;
        max-height: 100dvh;
        overflow: hidden;
    }
    body.home-landing .landing-hero--fullpage {
        position: relative;
        width: 100%;
        overflow: hidden;
        background-color: #0A0A0A;
        background-image: none;
    }
    body.home-landing .landing-hero-art {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        max-width: none;
        object-fit: cover;
        object-position: center 40%;
        image-rendering: auto;
        image-rendering: high-quality;
        z-index: 1;
        pointer-events: none;
        user-select: none;
        -webkit-user-drag: none;
    }
    @media (max-width: 767px) {
        body.home-landing .landing-hero-art { object-position: 14% 28%; }
    }
    @media (min-width: 768px) and (max-width: 1199px) {
        body.home-landing .landing-hero-art { object-position: 12% 36%; }
    }
    @media (min-width: 1800px) {
        body.home-landing .landing-hero-art { object-position: center 46%; }
    }
    body.home-landing .landing-hero-actions {
        background: linear-gradient(to top, rgba(10, 10, 10, .42), transparent 78%);
        padding-left: max(1rem, env(safe-area-inset-left, 0px));
        padding-right: max(1rem, env(safe-area-inset-right, 0px));
        padding-bottom: max(1rem, env(safe-area-inset-bottom, 0px));
    }
    body.home-landing .landing-hero-actions a {
        box-sizing: border-box;
        min-width: 11.5rem;
        height: 2.85rem;
        padding: 0 1.6rem;
        font-size: .95rem;
        font-weight: 700;
        line-height: 1;
        border-radius: 999px;
        border: 2px solid #C5A059;
        background: #C5A059;
        color: #0A0A0A;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        text-decoration: none;
        box-shadow: 0 8px 24px rgba(0,0,0,.45);
        transition: transform .15s ease, background .15s ease, border-color .15s ease;
    }
    body.home-landing .landing-hero-actions a:hover,
    body.home-landing .landing-hero-actions a:focus {
        background: #b08d45;
        border-color: #b08d45;
        color: #0A0A0A;
        transform: scale(1.04);
    }
    body.home-landing .landing-hero-actions a i {
        width: 1rem;
        height: 1rem;
        flex-shrink: 0;
        color: #0A0A0A;
    }
    body.home-landing .home-version {
        position: absolute;
        z-index: 4;
        right: max(1rem, env(safe-area-inset-right, 0px));
        bottom: max(.7rem, env(safe-area-inset-bottom, 0px));
        margin: 0;
        padding: .25rem .65rem;
        background: #0A0A0A;
        color: #F7F1E8;
        font-size: 11px;
        letter-spacing: .06em;
        line-height: 1.2;
        border-radius: 2px;
        pointer-events: none;
    }
</style>
@endpush

@section('content')
<section class="landing-hero landing-hero--fullpage" aria-label="{{ \App\Support\SiteBrand::siteTitle($general_setting ?? null) }}">
    <h1 class="sr-only">{!! \App\Support\SiteContent::html('home.hero_title', \App\Support\SiteBrand::siteTitle($general_setting ?? null)) !!}</h1>
    <img class="landing-hero-art"
         src="{{ \App\Support\SiteBrand::landingUrl('2x') }}"
         srcset="{{ \App\Support\SiteBrand::landingUrl('1x') }} 1280w, {{ \App\Support\SiteBrand::landingUrl('2x') }} 1678w, {{ \App\Support\SiteBrand::landingUrl('png') }} 1678w"
         sizes="100vw"
         width="1678"
         height="937"
         alt="{{ \App\Support\SiteBrand::siteTitle($general_setting ?? null) }}"
         decoding="async"
         fetchpriority="high">
    <div class="landing-hero-actions">
        <a href="{{ url('/register-now') }}">
            {{ \App\Support\SiteContent::text('home.cta_primary', __('site.home.join')) }} <i data-lucide="arrow-right"></i>
        </a>
        <a href="{{ url('/events') }}">
            <i data-lucide="calendar"></i> {{ \App\Support\SiteContent::text('home.cta_events', __('site.home.events')) }}
        </a>
        <a href="{{ url('/menu') }}">
            <i data-lucide="coffee"></i> {{ \App\Support\SiteContent::text('home.cta_cafe', __('site.home.cafe_menu')) }}
        </a>
    </div>
    <p class="home-version">{{ \App\Support\AppVersion::bcl() }}</p>
</section>
@endsection
