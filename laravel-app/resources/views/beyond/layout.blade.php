<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $siteLogoUrl = \App\Support\SiteBrand::logoUrl($general_setting ?? null);
        $siteTitle = \App\Support\SiteBrand::siteTitle($general_setting ?? null);
        $webUser = Auth::guard('web')->user();
        $beyondUser = Auth::guard('beyond')->user();
        $headerUser = $webUser ?: $beyondUser;
        $isAdminSession = (bool) $webUser;
        $headerName = $headerUser ? $headerUser->name : '';
        $headerWorkspace = \App\Support\UserWorkspaces::current($webUser);
        $wsLabel = $headerWorkspace ? (__('site.workspace.'.$headerWorkspace) ?: $headerWorkspace) : null;
        $headerRole = $wsLabel
            ?: ($isAdminSession
            ? __('site.nav.administrator')
            : (optional($beyondUser)->role
                ? strtoupper(str_replace('_', ' ', $beyondUser->role))
                : __('site.nav.user')));
        $headerInitial = $headerName !== '' ? mb_strtoupper(mb_substr($headerName, 0, 1)) : 'U';
        $shortName = \Illuminate\Support\Str::limit($headerName, 18, '…');
    @endphp
    <title>@yield('title', $siteTitle) | {{ $siteTitle }}</title>
    <meta name="description" content="@yield('meta_description', __('site.layout.meta'))">
    <link rel="icon" href="{{ $siteLogoUrl }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { blue: '#0A0A0A', dark: '#0A0A0A', light: '#C5A059', gold: '#C5A059', navy: '#0A0A0A', cream: '#F7F1E8' },
                    },
                    fontFamily: {
                        script: ['Great Vibes', 'cursive'],
                        serif: ['Cinzel', 'serif'],
                        sans: ['Montserrat', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700&family=Great+Vibes&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', ui-sans-serif, system-ui, sans-serif; }
        @keyframes floaty { 0%,100% { transform: translateY(0); opacity:.4 } 50% { transform: translateY(-20px); opacity:.9 } }
        .floaty { animation: floaty 4s ease-in-out infinite; }
        [x-cloak] { display:none !important; }

        @keyframes navLogoSpin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        /* Gold ↔ Silver metallic shift (no white plate / circle) */
        @keyframes navLogoMetal {
            0%, 100% {
                filter: sepia(1) saturate(4.2) hue-rotate(2deg) brightness(1.12) contrast(1.05)
                    drop-shadow(0 0 8px rgba(212,175,55,.7));
            }
            50% {
                filter: grayscale(1) brightness(1.45) contrast(1.15) saturate(0.2)
                    drop-shadow(0 0 8px rgba(220,220,230,.65));
            }
        }
        .nav-logo-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-right: .75rem;
            background: transparent;
            border: 0;
            padding: 0;
            box-shadow: none;
        }
        .nav-logo-spin {
            width: auto;
            height: 4.25rem;
            object-fit: contain;
            background: transparent;
            border-radius: 0;
        }
        @media (min-width: 768px) {
            .nav-logo-spin { height: 5.25rem; }
        }
        @media (min-width: 1024px) {
            .nav-logo-spin { height: 5.75rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            .nav-logo-spin { animation: none; }
        }

        .site-footer { background: transparent; color: #F7F1E8; }
        .site-footer-wave { display: block; width: 100%; line-height: 0; pointer-events: none; margin-bottom: -1px; }
        .site-footer-wave img,
        .site-footer-wave svg {
            display: block;
            width: 100%;
            height: clamp(2.25rem, 4.5vw, 3.75rem);
        }
        .site-footer-body { background: #0A0A0A; position: relative; overflow: hidden; }
        .site-footer-swoosh {
            position: absolute;
            right: -0.5rem;
            bottom: -0.25rem;
            width: min(16rem, 36vw);
            max-height: 88%;
            pointer-events: none;
            z-index: 0;
            line-height: 0;
        }
        .site-footer-swoosh svg,
        .site-footer-swoosh img {
            display: block;
            width: 100%;
            height: auto;
        }
        .site-footer-inner { position: relative; z-index: 1; }
        .site-footer-heading {
            display: flex;
            align-items: center;
            gap: .45rem;
            margin-bottom: .45rem;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: #fff;
        }
        .site-footer-heading i,
        .site-footer-heading svg { width: 1.05rem; height: 1.05rem; color: #C5A059; stroke: #C5A059; }
        .site-footer-item {
            display: flex;
            align-items: flex-start;
            gap: .45rem;
            color: #F7F1E8;
            font-size: .8rem;
            line-height: 1.35;
        }
        .site-footer-item::before {
            content: '';
            width: 0;
            height: 0;
            margin-top: .42em;
            border-style: solid;
            border-width: 5px 0 5px 7px;
            border-color: transparent transparent transparent #C5A059;
            flex-shrink: 0;
        }
        .site-footer-item:hover { color: #C5A059; }
        .site-footer-contact {
            display: flex;
            align-items: flex-start;
            gap: .55rem;
            color: #F7F1E8;
            font-size: .8rem;
            line-height: 1.3;
        }
        .site-footer-contact i,
        .site-footer-contact svg { width: 1rem; height: 1rem; color: #C5A059; stroke: #C5A059; margin-top: .15rem; flex-shrink: 0; }
        .site-footer-contact:hover { color: #C5A059; }
        .site-footer-divider { width: 1px; align-self: stretch; min-height: 2.5rem; background: #C5A059; opacity: .55; }

        body.home-landing {
            min-height: 100dvh;
            overflow-x: hidden;
        }
        body.home-landing main {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
            background: transparent;
        }
        .landing-hero--fullpage {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            min-height: 0;
            width: 100%;
        }
        .landing-hero-actions {
            position: relative;
            z-index: 2;
            flex-shrink: 0;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: .65rem;
            padding: .75rem 1rem 1rem;
            background: linear-gradient(to top, rgba(10,10,10,.55), transparent);
        }
        @media (min-width: 640px) {
            .landing-hero-actions { gap: .85rem; padding-bottom: 1.15rem; }
        }

        .site-footer-landing {
            position: relative;
            z-index: 2;
            background: transparent;
            color: #F7F1E8;
            margin-top: 0;
        }
        .site-footer-landing::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(10,10,10,.88) 0%, rgba(10,10,10,.45) 55%, transparent 100%);
            pointer-events: none;
            z-index: 0;
        }
        .site-footer-landing .site-footer-inner {
            position: relative;
            z-index: 1;
        }
    </style>
    @stack('head')
</head>
<body class="@yield('body_class', 'bg-brand-cream') text-gray-800 flex flex-col min-h-screen">

@php
    $headerCartCount = 0;
    if (session()->has('cart')) {
        foreach (session('cart') as $cartRow) {
            $headerCartCount += (int) ($cartRow['quantity'] ?? 0);
        }
    }
    $contactEmail = \App\Support\SiteBrand::email();
    $contactPhone = \App\Support\SiteBrand::phone();
    $contactWhatsAppDigits = \App\Support\SiteBrand::phoneWhatsAppDigits();
    $contactWebsite = \App\Support\SiteBrand::websiteLabel();
    $contactAddress = \App\Support\SiteBrand::address();
    $siteMarkUrl = \App\Support\SiteBrand::markUrl();
    $locale = app()->getLocale();
    $navDefs = [
        'home'     => ['label' => __('site.nav.home'), 'url' => url('/')],
        'about'    => ['label' => __('site.nav.about'), 'url' => url('/about')],
        'events'   => ['label' => __('site.nav.events'), 'url' => url('/events')],
        'menu'     => ['label' => __('site.nav.menu'), 'url' => url('/menu')],
        'register' => ['label' => __('site.nav.register'), 'url' => url('/register-now')],
    ];
    $navLinks = [];
    $navLabels = \App\Support\SiteMenu::itemLabels('landing_menu_labels', \App\Support\SiteMenu::landingItems());
    foreach (\App\Support\SiteMenu::landingOrder() as $navKey) {
        // Legacy saved menus may still include "contact" — skip; contact lives on About Us
        if ($navKey === 'contact' || \App\Support\SiteMenu::isHidden('landing_menu_hidden', $navKey)) {
            continue;
        }
        if (isset($navDefs[$navKey])) {
            $item = $navDefs[$navKey];
            if (! empty($navLabels[$navKey])) {
                $item['label'] = $navLabels[$navKey];
            }
            $navLinks[] = $item;
        }
    }
    $currentUrl = url()->current();
@endphp

<header class="bg-brand-navy sticky top-0 z-40 shadow-lg border-b border-brand-gold/40" x-data="{ open: false, userMenu: false, cartCount: {{ (int) $headerCartCount }} }" @keydown.escape.window="userMenu = false" @cart-updated.window="cartCount = $event.detail.number">
    <div class="w-full flex items-center justify-between h-[4.75rem] sm:h-[5.75rem] lg:h-[6.25rem] pl-2 pr-3 sm:pl-3 sm:pr-6 lg:pl-4 lg:pr-8">
        <a href="{{ url('/') }}" class="nav-logo-link" aria-label="{{ $siteTitle }} home">
            <img src="{{ $siteMarkUrl }}" alt="{{ $siteTitle }}" class="nav-logo-spin">
        </a>

        <nav class="hidden lg:flex items-center gap-x-4 xl:gap-x-6 flex-1 justify-center min-w-0">
            @foreach ($navLinks as $link)
                @php $active = rtrim($currentUrl,'/') === rtrim($link['url'],'/'); @endphp
                <a href="{{ $link['url'] }}"
                   class="text-lg xl:text-[1.3rem] font-medium transition-colors duration-300 whitespace-nowrap
                      @if($active) text-brand-gold border-b-2 border-brand-gold pb-1
                      @elseif(!empty($link['special'])) text-brand-gold hover:text-white font-bold
                      @else text-white hover:text-brand-gold @endif">
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="hidden lg:flex items-center shrink-0">
            <div class="flex items-center gap-2 xl:gap-3 mr-5 pr-4 border-r border-white/15">
            <div class="flex items-center gap-1 text-xs font-semibold">
                <a href="{{ url('/lang/en') }}" class="px-2 py-1 rounded {{ $locale === 'en' ? 'bg-brand-gold text-brand-blue' : 'text-white hover:text-brand-gold border border-white/20' }}">EN</a>
                <a href="{{ url('/lang/fr') }}" class="px-2 py-1 rounded {{ $locale === 'fr' ? 'bg-brand-gold text-brand-blue' : 'text-white hover:text-brand-gold border border-white/20' }}">FR</a>
            </div>

            @if ($contactPhone)
            <a href="{{ \App\Support\SiteBrand::phoneWhatsAppUrl() }}" target="_blank" rel="noopener" class="text-white hover:text-brand-gold transition-colors" title="{{ __('site.contact.whatsapp') }}">
                <i data-lucide="phone" class="w-5 h-5"></i>
            </a>
            @endif
            <a href="{{ \App\Support\SiteBrand::mapsUrl() }}" target="_blank" rel="noopener" class="text-white hover:text-brand-gold transition-colors" title="{{ __('site.contact.open_maps') }}">
                <i data-lucide="map-pin" class="w-5 h-5"></i>
            </a>
            <a href="{{ url('/cart') }}" class="relative text-white hover:text-brand-gold transition-colors" title="{{ __('site.nav.cart') }}">
                <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                <span x-show="cartCount > 0" x-cloak x-text="cartCount"
                      class="absolute -top-2 -right-2 min-w-[1.15rem] h-[1.15rem] px-1 rounded-full bg-brand-gold text-black text-[10px] font-bold flex items-center justify-center"></span>
            </a>
            </div>

            <div class="pl-1">

            @if ($headerUser)
                <div class="relative" @click.outside="userMenu = false">
                    <button type="button" @click="userMenu = !userMenu"
                            class="flex items-center gap-2.5 pl-1 pr-1 py-1 rounded-md hover:bg-white/10 transition-colors text-left">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 border-brand-gold bg-gradient-to-br from-brand-gold to-black text-black font-bold text-lg">
                            {{ $headerInitial }}
                        </span>
                        <span class="hidden xl:flex flex-col leading-tight min-w-0">
                            <span class="text-white font-semibold text-sm truncate max-w-[140px]">{{ $shortName }}</span>
                            <span class="text-brand-gold text-[11px] font-bold tracking-wide uppercase">{{ $headerRole }}</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-sky-200/90 shrink-0"></i>
                    </button>
                    <div x-show="userMenu" x-cloak x-transition
                         class="absolute right-0 mt-2 w-56 rounded-lg bg-white shadow-xl border border-gray-100 py-1 z-50">
                        <div class="px-4 py-2.5 text-sm font-bold text-gray-800">{{ __('site.nav.my_account') }}</div>
                        <div class="border-t border-gray-100"></div>
                        @include('beyond.partials.workspace_switcher')
                        @if ($isAdminSession)
                            <a href="{{ url('/admin') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-800 hover:bg-gray-50">
                                <i data-lucide="layout-grid" class="w-4 h-4 text-gray-700"></i> {{ __('site.nav.admin') }}
                            </a>
                            <a href="{{ url('/') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-800 hover:bg-gray-50">
                                <i data-lucide="home" class="w-4 h-4 text-gray-700"></i> {{ __('site.nav.home_page') }}
                            </a>
                        @else
                            <a href="{{ url('/user/profile') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-800 hover:bg-gray-50">
                                <i data-lucide="user" class="w-4 h-4 text-gray-700"></i> {{ __('site.nav.profile') }}
                            </a>
                            <a href="{{ url('/membership/account') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-800 hover:bg-gray-50">
                                <i data-lucide="id-card" class="w-4 h-4 text-gray-700"></i> {{ __('site.membership.account_title') }}
                            </a>
                        @endif
                        <form method="POST" action="{{ $isAdminSession ? route('logout') : route('beyond.logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50">
                                <i data-lucide="log-out" class="w-4 h-4"></i> {{ __('site.nav.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ url('/login') }}" class="bg-transparent border border-brand-gold text-brand-gold hover:bg-brand-gold hover:text-black font-medium transition-all rounded-md px-4 py-2 flex items-center gap-2">
                    <i data-lucide="log-in" class="w-4 h-4"></i> {{ __('site.nav.login') }}
                </a>
            @endif
            </div>
        </div>

        <button @click="open = !open" class="lg:hidden text-white hover:text-brand-gold transition-colors">
            <i data-lucide="menu" class="w-6 h-6" x-show="!open"></i>
            <i data-lucide="x" class="w-6 h-6" x-show="open" x-cloak></i>
        </button>
    </div>

    <div x-show="open" x-cloak class="lg:hidden pb-4 px-4 bg-black border-t border-brand-gold/30">
        <nav class="flex flex-col space-y-3 pt-4">
            @foreach ($navLinks as $link)
                <a href="{{ $link['url'] }}" class="text-[1.46rem] font-medium {{ !empty($link['special']) ? 'text-brand-gold' : 'text-white hover:text-brand-gold' }}">{{ $link['label'] }}</a>
            @endforeach
            <div class="flex items-center gap-2 pt-1">
                @if ($contactPhone)
                <a href="{{ \App\Support\SiteBrand::phoneWhatsAppUrl() }}" target="_blank" rel="noopener" class="text-white hover:text-brand-gold" title="{{ __('site.contact.whatsapp') }}">
                    <i data-lucide="message-circle" class="w-5 h-5"></i>
                </a>
                @endif
                <a href="{{ \App\Support\SiteBrand::mapsUrl() }}" target="_blank" rel="noopener" class="text-white hover:text-brand-gold" title="{{ __('site.contact.open_maps') }}">
                    <i data-lucide="map-pin" class="w-5 h-5"></i>
                </a>
                <a href="{{ url('/lang/en') }}" class="px-2 py-1 text-sm rounded {{ $locale === 'en' ? 'bg-brand-gold text-black' : 'border border-white/20 text-white' }}">EN</a>
                <a href="{{ url('/lang/fr') }}" class="px-2 py-1 text-sm rounded {{ $locale === 'fr' ? 'bg-brand-gold text-black' : 'border border-white/20 text-white' }}">FR</a>
            </div>
            <a href="{{ url('/cart') }}" class="flex items-center gap-2 text-lg font-medium text-white hover:text-brand-gold">
                {{ __('site.nav.cart') }}
                <span x-show="cartCount > 0" x-cloak x-text="cartCount"
                      class="min-w-[1.25rem] h-5 px-1.5 rounded-full bg-brand-gold text-black text-xs font-bold flex items-center justify-center"></span>
            </a>
            <div class="pt-3 border-t border-white/10 space-y-2">
                @if ($headerUser)
                    <div class="flex items-center gap-3 px-1 py-2">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-brand-gold bg-brand-gold text-black font-bold">{{ $headerInitial }}</span>
                        <div>
                            <div class="text-white font-semibold text-sm">{{ $headerName }}</div>
                            <div class="text-brand-gold text-xs font-bold uppercase">{{ $headerRole }}</div>
                        </div>
                    </div>
                    <div class="rounded-lg bg-white/5 py-1">
                        @include('beyond.partials.workspace_switcher')
                    </div>
                    @if ($isAdminSession)
                        <a href="{{ url('/admin') }}" class="flex items-center justify-center gap-2 w-full py-2 rounded bg-brand-gold text-black font-bold">{{ __('site.nav.admin') }}</a>
                        <a href="{{ url('/') }}" class="flex items-center justify-center gap-2 w-full py-2 rounded border border-white/20 text-white">{{ __('site.nav.home_page') }}</a>
                    @else
                        <a href="{{ url('/user/profile') }}" class="flex items-center justify-center gap-2 w-full py-2 rounded bg-brand-gold text-black font-bold">{{ __('site.nav.profile') }}</a>
                        <a href="{{ url('/membership/account') }}" class="flex items-center justify-center gap-2 w-full py-2 rounded border border-white/20 text-white">{{ __('site.membership.account_title') }}</a>
                    @endif
                    <form method="POST" action="{{ $isAdminSession ? route('logout') : route('beyond.logout') }}">
                        @csrf
                        <button type="submit" class="w-full py-2 rounded border border-red-400/50 text-red-300">{{ __('site.nav.logout') }}</button>
                    </form>
                @else
                    <a href="{{ url('/login') }}" class="flex items-center justify-center gap-2 w-full py-2 rounded border border-brand-gold text-brand-gold font-medium">
                        <i data-lucide="log-in" class="w-5 h-5"></i> {{ __('site.nav.login') }}
                    </a>
                @endif
            </div>
        </nav>
    </div>
</header>

<main class="flex-1 min-h-0">
    @yield('content')
</main>

@unless(trim($__env->yieldContent('hide_footer')))
@php
    $isLandingFooter = (bool) trim($__env->yieldContent('landing_footer'));
    $waDigits = $contactWhatsAppDigits;
@endphp
<footer class="{{ $isLandingFooter ? 'site-footer-landing' : 'site-footer mt-auto' }}">
    @unless($isLandingFooter)
    <div class="bg-brand-cream h-4 sm:h-5" aria-hidden="true"></div>
    <div class="site-footer-wave" aria-hidden="true">
        <img src="{{ url('public/branding/footer-wave.svg') }}?v=5" alt="" width="1024" height="63">
    </div>
    @endunless
    <div class="{{ $isLandingFooter ? '' : 'site-footer-body' }}">
        @unless($isLandingFooter)
        <div class="site-footer-swoosh" aria-hidden="true">
            <img src="{{ url('public/branding/footer-swoosh.svg') }}?v=2" alt="" width="420" height="380">
        </div>
        @endunless
        <div class="site-footer-inner">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 {{ $isLandingFooter ? 'pt-2 pb-2' : 'pt-1 pb-3' }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 lg:gap-10">
                <div class="min-w-0">
                    <h3 class="site-footer-heading">
                        <i data-lucide="clipboard-check"></i>
                        {{ \App\Support\SiteContent::text('footer.services_heading', __('site.footer.services')) }}
                    </h3>
                    <nav class="space-y-1.5">
                        <a href="{{ url('/menu') }}" class="site-footer-item">{{ \App\Support\SiteContent::text('footer.service_dining', __('site.footer.service_dining')) }}</a>
                        <a href="{{ url('/about') }}" class="site-footer-item">{{ \App\Support\SiteContent::text('footer.service_lounge', __('site.footer.service_lounge')) }}</a>
                    </nav>
                </div>

                <div class="min-w-0">
                    <h3 class="site-footer-heading">
                        <i data-lucide="user"></i>
                        {{ \App\Support\SiteContent::text('footer.contact_heading', __('site.footer.contact')) }}
                    </h3>
                    <div class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-5">
                        <div class="space-y-2 min-w-0">
                            <a href="{{ \App\Support\SiteBrand::phoneWhatsAppUrl() }}" target="_blank" rel="noopener" class="site-footer-contact">
                                <i data-lucide="message-circle"></i>
                                <span>{{ $contactPhone }}</span>
                            </a>
                            <a href="{{ \App\Support\SiteBrand::mapsUrl() }}" target="_blank" rel="noopener" class="site-footer-contact">
                                <i data-lucide="map-pin"></i>
                                <span>{{ $contactAddress }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="px-4 sm:px-6 pb-2">
            <p class="text-center text-[11px] leading-relaxed text-white/70">
                © {{ date('Y') }} Welcome 2 Kigali Expats Club. {{ \App\Support\SiteContent::text('footer.rights', __('site.footer.rights')) }}
                <span class="text-white/30"> | </span>
                {{ __('site.footer.developed') }} <span class="text-white font-medium">Sr. Engr. Tefu R. Mbole</span>
                <span class="text-white/30"> | </span>
                <a href="https://wa.me/{{ $waDigits }}" target="_blank" rel="noopener" class="text-white/80 hover:text-brand-gold">{{ $contactPhone }}</a>
                <span class="text-white/30"> | </span>
                {{ \App\Support\AppVersion::bcl() }}
            </p>
        </div>
        </div>
    </div>
</footer>
@endunless

@unless(trim($__env->yieldContent('hide_footer')))
<a href="{{ \App\Support\SiteBrand::phoneWhatsAppUrl() }}" target="_blank" rel="noopener"
   class="fixed bottom-6 right-6 z-50 bg-[#25D366] hover:bg-[#1EBE57] text-white rounded-full p-4 shadow-xl hover:shadow-2xl transition-all flex items-center justify-center"
   title="Chat on WhatsApp">
    <i data-lucide="message-circle" class="w-6 h-6"></i>
</a>
@endunless

<script src="https://unpkg.com/lucide@latest"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    window.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
    document.addEventListener('alpine:initialized', () => { if (window.lucide) lucide.createIcons(); });
</script>
@stack('scripts')
@include('components.whatsapp_phone_script')
<script>
(function () {
    if (window.__eventCountdownInit) return;
    window.__eventCountdownInit = true;
    function pad(n) { return n < 10 ? '0' + n : String(n); }
    function bindCountdown(el) {
        if (el.__countdownBound) return;
        el.__countdownBound = true;
        var targetIso = el.getAttribute('data-target');
        if (!targetIso) return;
        var hideAfter = el.getAttribute('data-hide-after') === '1';
        var doneMsg = el.querySelector('[data-done]');
        var units = el.querySelector('[data-units]');
        var target = new Date(targetIso).getTime();
        if (isNaN(target)) return;
        function tick() {
            var diff = target - Date.now();
            if (diff <= 0) {
                if (units) units.classList.add('hidden');
                if (doneMsg) doneMsg.classList.remove('hidden');
                if (hideAfter) setTimeout(function () { el.style.display = 'none'; }, 8000);
                return false;
            }
            var secs = Math.floor(diff / 1000);
            var days = Math.floor(secs / 86400); secs %= 86400;
            var hours = Math.floor(secs / 3600); secs %= 3600;
            var mins = Math.floor(secs / 60); secs %= 60;
            var d = el.querySelector('.cd-days');
            var h = el.querySelector('.cd-hours');
            var m = el.querySelector('.cd-mins');
            var s = el.querySelector('.cd-secs');
            if (d) d.textContent = days;
            if (h) h.textContent = pad(hours);
            if (m) m.textContent = pad(mins);
            if (s) s.textContent = pad(secs);
            return true;
        }
        if (tick()) setInterval(tick, 1000);
    }
    document.querySelectorAll('[data-countdown]').forEach(bindCountdown);
})();
</script>
</body>
</html>
