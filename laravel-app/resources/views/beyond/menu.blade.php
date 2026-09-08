@extends('beyond.layout')

@section('title', __('site.menu.title'))
@section('meta_description', __('site.menu.meta'))
@section('body_class', 'bg-black')

@push('head')
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;1,400&display=swap" rel="stylesheet">
<style>
    .menu-book { background: #070707; padding-bottom: 5.5rem; }
    .menu-cat-nav {
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .menu-cat-nav::-webkit-scrollbar { display: none; }
    .menu-chip {
        scroll-snap-align: start;
        min-height: 2.65rem;
        border-radius: 999px;
        padding: .55rem 1rem;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .menu-sheet {
        position: relative;
        overflow: hidden;
        max-width: 1180px;
        margin: .75rem auto;
        background: #0c0c0c;
        border: 1px solid rgba(197,160,89,.35);
        border-radius: 1.25rem;
    }
    .menu-hero {
        position: relative;
        min-height: 11.5rem;
        overflow: hidden;
    }
    .menu-hero img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        min-height: 11.5rem;
    }
    .menu-hero:after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(7,7,7,.92) 0%, rgba(7,7,7,.35) 48%, rgba(7,7,7,.15) 100%);
    }
    .menu-hero-copy {
        position: absolute;
        left: 0; right: 0; bottom: 0;
        z-index: 2;
        padding: 1rem 1.1rem 1.05rem;
    }
    .menu-list { padding: .35rem .7rem 1rem; }
    .menu-cat {
        font-family: Cinzel, serif;
        color: #C5A059;
        letter-spacing: .16em;
        text-transform: uppercase;
        font-size: .72rem;
        margin: .85rem .2rem .35rem;
    }
    .menu-item {
        border-radius: .85rem;
        padding: .15rem 0;
    }
    .menu-item-btn {
        width: 100%;
        text-align: left;
        min-height: 3.1rem;
        padding: .7rem .65rem;
        border-radius: .85rem;
        display: block;
    }
    .menu-item-btn:active { background: rgba(197,160,89,.12); }
    .menu-item-line {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
        width: 100%;
    }
    .menu-item-name {
        font-family: "Lora", "Times New Roman", serif;
        font-size: 1.12rem;
        font-weight: 500;
        color: #F7F1E8;
        line-height: 1.3;
        flex: 1 1 auto;
        min-width: 0;
    }
    .menu-item-price {
        font-family: "Lora", "Times New Roman", serif;
        font-size: 1.12rem;
        font-weight: 600;
        color: #C5A059;
        white-space: nowrap;
        flex: 0 0 auto;
        font-variant-numeric: tabular-nums;
    }
    .menu-item-note {
        font-family: "Lora", serif;
        font-size: .92rem;
        font-style: italic;
        color: rgba(247,241,232,.55);
    }
    .menu-dots { display: none; }
    .menu-qty {
        display: flex;
        align-items: center;
        gap: .55rem;
        padding: .15rem .65rem .85rem;
    }
    .menu-qty-btn {
        width: 2.6rem;
        height: 2.6rem;
        border-radius: 999px;
        border: 1px solid rgba(197,160,89,.75);
        color: #C5A059;
        font-size: 1.35rem;
        line-height: 1;
        flex-shrink: 0;
    }
    .menu-qty-num {
        min-width: 1.6rem;
        text-align: center;
        font-family: "Lora", serif;
        font-size: 1.2rem;
        color: #F7F1E8;
    }
    .menu-qty-add {
        margin-left: auto;
        border-radius: 999px;
        background: #C5A059;
        color: #0A0A0A;
        padding: .7rem 1.1rem;
        font-size: .7rem;
        letter-spacing: .14em;
        text-transform: uppercase;
        font-weight: 700;
        min-height: 2.6rem;
    }
    .menu-qty-add:disabled { opacity: .55; }
    .menu-flavor {
        border-radius: 999px;
        padding: .4rem .7rem;
        font-size: .68rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        border: 1px solid rgba(197,160,89,.4);
        color: rgba(197,160,89,.95);
    }
    .menu-cart-bar {
        position: fixed;
        left: .75rem;
        right: 4.75rem;
        bottom: .85rem;
        z-index: 45;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        background: #C5A059;
        color: #0A0A0A;
        border-radius: 999px;
        padding: .7rem 1rem .7rem 1.15rem;
        font-weight: 700;
        box-shadow: 0 10px 30px rgba(0,0,0,.45);
        min-height: 3.15rem;
    }
    .menu-sheet--coffee .menu-hero img { object-position: 80% 46%; }
    .menu-sheet--iced .menu-hero img { object-position: 76% 48%; }
    .menu-sheet--tea .menu-hero img { object-position: 74% 42%; }
    .menu-sheet--juice .menu-hero img,
    .menu-sheet--smoothies .menu-hero img { object-position: 72% 48%; }
    @media (min-width: 768px) {
        .menu-hero { min-height: 15rem; }
        .menu-hero img { min-height: 15rem; }
        .menu-item-name, .menu-item-price { font-size: 1.28rem; }
        .menu-list { padding: .5rem 1.15rem 1.15rem; }
    }
    @media (min-width: 1024px) {
        .menu-book { padding-bottom: 2rem; }
        .menu-sheet {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(280px, .9fr);
            margin: 1.1rem auto;
            border-radius: 1.5rem;
        }
        .menu-hero {
            grid-column: 2;
            grid-row: 1 / span 2;
            min-height: 100%;
        }
        .menu-hero img { min-height: 100%; position: absolute; inset: 0; }
        .menu-hero:after {
            background: linear-gradient(to top, rgba(7,7,7,.82) 0%, rgba(7,7,7,.12) 55%, transparent 100%);
        }
        .menu-list { grid-column: 1; grid-row: 1; padding: 1.15rem 1.25rem 1.4rem; }
        .menu-dots {
            display: block;
            flex: 1 0 1.5rem;
            min-width: 1.5rem;
            margin: 0 .35rem;
            border-bottom: 1px dotted rgba(197,160,89,.5);
            transform: translateY(-0.42em);
        }
        .menu-item-line { align-items: baseline; }
        .menu-item-name, .menu-item-price { font-size: 1.38rem; }
        .menu-cart-bar { display: none; }
        .menu-item-btn:hover { background: rgba(197,160,89,.08); }
    }
</style>
@endpush

@section('content')
@php
    $pageNo = 0;
@endphp

<div x-data="w2kMenuCart({{ (int) ($cartCount ?? 0) }}, {{ json_encode(isset($groups[0]['id']) ? 'menu-'.$groups[0]['id'] : '') }})" class="menu-book text-white">
    <div class="menu-cat-nav sticky top-[4.75rem] sm:top-[5.75rem] lg:top-[6.25rem] z-30 bg-black/95 backdrop-blur border-b border-brand-gold/20">
        <div class="max-w-[1180px] mx-auto px-3 py-2 flex gap-2 overflow-x-auto snap-x">
            @foreach ($groups as $navGroup)
                <a href="#menu-{{ $navGroup['id'] }}"
                   @click="activeId = 'menu-{{ $navGroup['id'] }}'"
                   :class="activeId === 'menu-{{ $navGroup['id'] }}'
                       ? 'bg-brand-gold text-black border-brand-gold'
                       : 'border-brand-gold/40 text-brand-gold'"
                   class="menu-chip shrink-0 border">
                    {{ \App\Support\SiteI18n::category($navGroup['name']) }}
                </a>
            @endforeach
        </div>
    </div>

    @if (empty($groups) || count($groups) === 0)
        <section class="py-24 px-4 text-center">
            <p class="text-gray-300 mb-3">{{ __('site.menu.empty') }}</p>
        </section>
    @else
        @foreach ($groups as $group)
            @php
                $pageNo++;
                $sheetMod = '';
                if (($group['name'] ?? '') === 'Smoothies') {
                    $sheetMod = ' menu-sheet--smoothies';
                } elseif (($group['name'] ?? '') === 'Coffee & Tea') {
                    $sheetMod = ' menu-sheet--coffee';
                } elseif (($group['name'] ?? '') === 'Iced & Specialty') {
                    $sheetMod = ' menu-sheet--iced';
                } elseif (($group['name'] ?? '') === 'Tea & Hot Beverages') {
                    $sheetMod = ' menu-sheet--tea';
                } elseif (($group['name'] ?? '') === 'Fresh & Detox Juices') {
                    $sheetMod = ' menu-sheet--juice';
                }
                $buckets = [];
                if (($group['name'] ?? '') === 'Iced & Specialty') {
                    $iced = [];
                    $specialty = [];
                    foreach ($group['items'] as $bucketItem) {
                        if (strpos($bucketItem['name'], 'Ice ') === 0) {
                            $iced[] = $bucketItem;
                        } else {
                            $specialty[] = $bucketItem;
                        }
                    }
                    $buckets = [
                        ['title' => 'Iced Coffees', 'items' => $iced],
                        ['title' => 'Specialty Coffees / Brewing', 'items' => $specialty],
                    ];
                } elseif (($group['name'] ?? '') === 'Coffee & Tea') {
                    $buckets = [['title' => 'Espresso Beverages', 'items' => $group['items']]];
                } elseif (($group['name'] ?? '') === 'Fresh & Detox Juices') {
                    $fresh = [];
                    $detox = [];
                    foreach ($group['items'] as $bucketItem) {
                        if (stripos($bucketItem['name'], 'Detox') !== false) {
                            $detox[] = $bucketItem;
                        } else {
                            $fresh[] = $bucketItem;
                        }
                    }
                    $buckets = [
                        ['title' => 'Fresh Juices', 'items' => $fresh],
                        ['title' => 'Detox Juices', 'items' => $detox],
                    ];
                } else {
                    $buckets = [['title' => $group['name'], 'items' => $group['items']]];
                }
            @endphp
            <section id="menu-{{ $group['id'] }}" class="menu-section px-3 sm:px-4 scroll-mt-[8.25rem] sm:scroll-mt-[9.5rem] lg:scroll-mt-[10.25rem]">
                <article class="menu-sheet{{ $sheetMod }}">
                    <div class="menu-hero">
                        @if (!empty($group['image']))
                            <img src="{{ $group['image'] }}" alt="" class="menu-photo">
                        @endif
                        <div class="menu-hero-copy">
                            <p class="text-[10px] tracking-[0.22em] uppercase text-brand-gold/90 m-0">{{ \App\Support\SiteI18n::tagline($group['name']) }}</p>
                            <h2 class="font-serif text-[1.65rem] sm:text-[2.1rem] leading-tight text-[#F7F1E8] m-0 mt-1">{{ \App\Support\SiteI18n::category($group['name']) }}</h2>
                        </div>
                    </div>
                    <div class="menu-list">
                            @if (count($group['items']) === 0)
                                <p class="text-white/60 italic py-8 px-2">{{ __('site.menu.food_empty') }}</p>
                            @else
                                @foreach ($buckets as $bucket)
                                    @if ($bucket['title'] && count($bucket['items']))
                                        <p class="menu-cat">{{ \App\Support\SiteI18n::bucket($bucket['title']) }}</p>
                                    @endif
                                    <div>
                                        @foreach ($bucket['items'] as $item)
                                            @php $itemKey = (string) $item['id']; @endphp
                                            <div class="menu-item" :class="isOpen({{ json_encode($itemKey) }}) ? 'bg-white/5' : ''">
                                                <button type="button"
                                                        class="menu-item-btn"
                                                        @click="openItem({{ (int) $item['id'] }}, {{ json_encode(\App\Support\SiteI18n::item($item['name'])) }})">
                                                    <div class="menu-item-line">
                                                        <span class="menu-item-name">
                                                            {{ \App\Support\SiteI18n::item($item['name']) }}
                                                            @if (!empty($item['details']) && empty($item['flavors']))
                                                                <span class="menu-item-note">({{ \App\Support\SiteI18n::details($item['details']) }})</span>
                                                            @endif
                                                        </span>
                                                        <span class="menu-dots" aria-hidden="true"></span>
                                                        <span class="menu-item-price">{{ number_format($item['price']) }}</span>
                                                    </div>
                                                </button>
                                                @if (!empty($item['flavors']))
                                                    <p class="menu-item-note px-2.5 -mt-0.5 mb-1.5">{{ implode(', ', array_map([\App\Support\SiteI18n::class, 'flavor'], $item['flavors'])) }}</p>
                                                    <div class="flex flex-wrap gap-1.5 px-2.5 pb-1">
                                                        @foreach ($item['flavors'] as $flavor)
                                                            <button type="button"
                                                                    class="menu-flavor hover:bg-brand-gold hover:text-black transition-colors"
                                                                    @click="openItem({{ (int) $item['id'] }}, {{ json_encode(\App\Support\SiteI18n::item($item['name']).' — '.\App\Support\SiteI18n::flavor($flavor)) }}, {{ json_encode($flavor) }})">
                                                                {{ \App\Support\SiteI18n::flavor($flavor) }}
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                @endif
                                                <div class="menu-qty" x-show="isOpen({{ json_encode($itemKey) }})" x-cloak x-transition>
                                                    <button type="button" class="menu-qty-btn" @click="qty = Math.max(1, qty - 1)">−</button>
                                                    <span class="menu-qty-num" x-text="qty"></span>
                                                    <button type="button" class="menu-qty-btn" @click="qty++">+</button>
                                                    <button type="button" class="menu-qty-add" :disabled="busy" @click="addOpen()">{{ __('site.menu.add') }}</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            @endif
                    </div>
                </article>
            </section>
        @endforeach
    @endif

    <a href="{{ url('/cart') }}" class="menu-cart-bar" x-show="cartCount > 0" x-cloak>
        <span>{{ __('site.menu.your_order') }} <span x-text="'(' + cartCount + ')'"></span></span>
        <span class="uppercase tracking-widest text-xs">{{ __('site.menu.view_cart') }} →</span>
    </a>

    <div x-show="toast" x-cloak x-transition
         class="fixed bottom-24 left-4 z-50 max-w-sm rounded-2xl border border-brand-gold bg-black/95 px-4 py-3 shadow-2xl lg:bottom-6 lg:left-6">
        <p class="text-brand-gold text-xs tracking-[0.2em] uppercase">{{ __('site.menu.added') }}</p>
        <p class="text-white text-sm mt-1" x-text="toast"></p>
        <a href="{{ url('/cart') }}" class="inline-block mt-2 text-xs uppercase tracking-widest text-brand-gold underline">{{ __('site.menu.view_cart') }}</a>
    </div>
</div>
@endsection

@push('scripts')
<script>
function w2kMenuCart(initialCount, firstId) {
    return {
        busy: false,
        toast: '',
        cartCount: initialCount || 0,
        activeId: firstId || '',
        openKey: '',
        openId: 0,
        openName: '',
        openOption: '',
        qty: 1,
        isOpen(id) {
            return String(this.openId) === String(id);
        },
        openItem(id, name, option) {
            var key = option ? id + '::' + option : String(id);
            if (this.openKey === key) {
                return;
            }
            this.openKey = key;
            this.openId = id;
            this.openName = name;
            this.openOption = option || '';
            this.qty = 1;
        },
        init() {
            var self = this;
            var sections = Array.prototype.slice.call(document.querySelectorAll('.menu-section[id^="menu-"]'));
            if (!sections.length) return;
            var onScroll = function () {
                var header = document.querySelector('header');
                var sticky = document.querySelector('.menu-cat-nav');
                var offset = (header ? header.offsetHeight : 72) + (sticky ? sticky.offsetHeight : 36) + 12;
                var current = sections[0].id;
                for (var i = 0; i < sections.length; i++) {
                    if (sections[i].getBoundingClientRect().top <= offset) {
                        current = sections[i].id;
                    }
                }
                self.activeId = current;
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        },
        addOpen() {
            this.addItem(this.openId, this.openName, this.openOption, this.qty);
        },
        async addItem(id, name, option, quantity) {
            if (this.busy || !id) return;
            this.busy = true;
            try {
                const params = new URLSearchParams({
                    id: String(id),
                    quantity: String(quantity || this.qty || 1)
                });
                if (option) params.set('option', option);
                const res = await fetch('{{ url('/addToCart') }}?' + params.toString(), {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.error || @json(__('site.menu.add_error')));
                this.cartCount = data.number || 0;
                this.toast = name + ' × ' + (quantity || this.qty);
                window.dispatchEvent(new CustomEvent('cart-updated', { detail: { number: data.number || 0 } }));
                setTimeout(() => { if (this.toast.indexOf(name) === 0) this.toast = ''; }, 3200);
            } catch (e) {
                this.toast = e.message || @json(__('site.menu.add_error'));
            } finally {
                this.busy = false;
            }
        }
    };
}
</script>
@endpush
