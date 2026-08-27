@extends('beyond.layout')

@section('title', __('site.menu.title'))
@section('meta_description', __('site.menu.meta'))
@section('body_class', 'bg-black')

@push('head')
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;1,400&display=swap" rel="stylesheet">
<style>
    .menu-book { background: #070707; }
    .menu-sheet {
        position: relative;
        max-width: 1180px;
        margin: 1.1rem auto;
        padding: 1.35rem 1.15rem 1rem;
        background:
            radial-gradient(ellipse at 80% 70%, rgba(197,160,89,.07), transparent 42%),
            linear-gradient(180deg, #0c0c0c 0%, #080808 100%);
        border: 1px solid #C5A059;
        box-shadow:
            inset 0 0 0 7px #070707,
            inset 0 0 0 8px rgba(197,160,89,.55);
    }
    .menu-sheet:before,
    .menu-sheet:after {
        content: "✦";
        position: absolute;
        color: #C5A059;
        font-size: 11px;
        line-height: 1;
        z-index: 2;
    }
    .menu-sheet:before { top: 14px; left: 16px; }
    .menu-sheet:after { top: 14px; right: 16px; }
    .menu-sheet-foot:before,
    .menu-sheet-foot:after {
        content: "✦";
        position: absolute;
        color: #C5A059;
        font-size: 11px;
    }
    .menu-sheet-foot:before { left: 8px; bottom: 2px; }
    .menu-sheet-foot:after { right: 8px; bottom: 2px; }
    .menu-item-line {
        display: flex;
        align-items: baseline;
        width: 100%;
        min-width: 0;
    }
    .menu-item-name {
        font-family: "Lora", "Times New Roman", serif;
        font-size: 1.48rem;
        font-weight: 500;
        color: #F7F1E8;
        letter-spacing: 0;
        line-height: 1.3;
        flex: 0 1 auto;
        min-width: 0;
    }
    .menu-item-price {
        font-family: "Lora", "Times New Roman", serif;
        font-size: 1.48rem;
        font-weight: 600;
        color: #C5A059;
        white-space: nowrap;
        letter-spacing: 0;
        flex: 0 0 4.6rem;
        text-align: right;
        font-variant-numeric: tabular-nums;
    }
    .menu-item-note {
        font-family: "Lora", "Times New Roman", serif;
        font-size: 1.08rem;
        font-style: italic;
        font-weight: 400;
        color: rgba(247,241,232,.55);
    }
    .menu-dots {
        flex: 1 0 1.85rem;
        min-width: 1.85rem;
        margin: 0 .4rem;
        border-bottom: 1px dotted rgba(197,160,89,.55);
        transform: translateY(-0.42em);
    }
    .menu-sheet--smoothies .menu-item-name {
        font-size: 1.32rem;
        white-space: nowrap;
    }
    .menu-sheet--smoothies .menu-item-price {
        font-size: 1.32rem;
        flex-basis: 4.35rem;
    }
    .menu-sheet--smoothies .menu-item-note {
        font-size: .98rem;
    }
    .menu-item-btn {
        width: 100%;
        text-align: left;
        padding: .5rem .15rem;
    }
    .menu-qty {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .55rem;
        padding: .35rem .15rem .7rem;
    }
    .menu-qty-btn {
        width: 2.1rem;
        height: 2.1rem;
        border: 1px solid rgba(197,160,89,.7);
        color: #C5A059;
        font-size: 1.25rem;
        line-height: 1;
    }
    .menu-qty-btn:hover { background: rgba(197,160,89,.12); }
    .menu-qty-num {
        min-width: 1.75rem;
        text-align: center;
        font-family: "Lora", serif;
        font-size: 1.25rem;
        color: #F7F1E8;
    }
    .menu-qty-add {
        border: 1px solid #C5A059;
        background: #C5A059;
        color: #0A0A0A;
        padding: .4rem .85rem;
        font-size: .72rem;
        letter-spacing: .16em;
        text-transform: uppercase;
        font-weight: 700;
    }
    .menu-item-btn:hover { background: rgba(197,160,89,.07); }
    .menu-item-btn:hover .menu-dots { border-bottom-color: rgba(197,160,89,.9); }
    .menu-item-btn:disabled { opacity: .55; }
    .menu-sheet-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: .85rem;
        align-items: stretch;
    }
    .menu-visual {
        position: relative;
        display: flex;
        flex-direction: column;
        min-height: 16rem;
    }
    .menu-visual-copy {
        position: relative;
        z-index: 2;
        text-align: right;
        padding: 0 .15rem .35rem;
    }
    .menu-photo-wrap {
        flex: 1 1 auto;
        overflow: hidden;
        min-height: 18rem;
        margin: 0 -.15rem;
    }
    .menu-photo {
        width: 100%;
        height: 100%;
        min-height: 18rem;
        object-fit: cover;
        object-position: 28% 50%;
        display: block;
    }
    .menu-sheet--coffee .menu-photo { object-position: 18% 46%; }
    .menu-sheet--iced .menu-photo { object-position: 36% 48%; }
    .menu-sheet--tea .menu-photo { object-position: 40% 42%; }
    .menu-sheet--juice .menu-photo,
    .menu-sheet--smoothies .menu-photo { object-position: 46% 48%; }
    .menu-cat {
        font-family: Cinzel, serif;
        color: #C5A059;
        letter-spacing: .18em;
        text-transform: uppercase;
        font-size: .92rem;
        margin: 1.05rem 0 .45rem;
    }
    @media (min-width: 1024px) {
        .menu-sheet-grid {
            grid-template-columns: minmax(0, 1.08fr) minmax(0, .92fr);
            gap: 0 .15rem;
        }
        .menu-visual {
            min-height: 100%;
            min-height: 28rem;
        }
        .menu-visual-copy {
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            z-index: 2;
            padding: .1rem .15rem 2.75rem;
            background: linear-gradient(180deg, rgba(12,12,12,.9) 0%, rgba(12,12,12,.35) 58%, transparent 100%);
        }
        .menu-photo-wrap {
            position: absolute;
            inset: 0;
            margin-left: -1.6rem;
            min-height: 100%;
        }
        .menu-photo {
            min-height: 100%;
            height: 100%;
        }
    }
    @media (max-width: 1023px) {
        .menu-sheet { margin: .65rem .55rem; padding: 1rem .8rem .75rem; }
        .menu-item-name, .menu-item-price { font-size: 1.22rem; }
        .menu-sheet--smoothies .menu-item-name,
        .menu-sheet--smoothies .menu-item-price { font-size: 1.12rem; }
        .menu-photo { min-height: 16rem; object-position: 40% 45%; }
        .menu-item-name { white-space: normal; }
    }
</style>
@endpush

@section('content')
@php
    $siteLogo = \App\Support\SiteBrand::logoUrl($general_setting ?? null);
    $pageNo = 0;
@endphp

<div x-data="w2kMenuCart({{ (int) ($cartCount ?? 0) }}, {{ json_encode(isset($groups[0]['id']) ? 'menu-'.$groups[0]['id'] : '') }})" class="menu-book text-white">
    <div class="menu-cat-nav sticky top-[4.75rem] sm:top-[5.75rem] lg:top-[6.25rem] z-30 bg-black/92 backdrop-blur border-b border-brand-gold/25">
        <div class="max-w-[1180px] mx-auto px-4 py-1.5 flex gap-2 overflow-x-auto">
            @foreach ($groups as $navGroup)
                <a href="#menu-{{ $navGroup['id'] }}"
                   @click="activeId = 'menu-{{ $navGroup['id'] }}'"
                   :class="activeId === 'menu-{{ $navGroup['id'] }}'
                       ? 'bg-brand-gold text-black border-brand-gold'
                       : 'border-brand-gold/40 text-brand-gold hover:bg-brand-gold hover:text-black'"
                   class="shrink-0 px-3 py-1.5 text-[12.8px] font-bold tracking-[0.14em] uppercase border transition-colors">
                    {{ \App\Support\SiteI18n::category($navGroup['name']) }}
                </a>
            @endforeach
            <a href="{{ url('/cart') }}"
               class="shrink-0 ml-auto px-3 py-1.5 text-[12.8px] font-bold tracking-[0.14em] uppercase border border-brand-gold bg-brand-gold text-black">
                {{ __('site.nav.cart') }} <span x-show="cartCount > 0" x-cloak x-text="'(' + cartCount + ')'"></span>
            </a>
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
            <section id="menu-{{ $group['id'] }}" class="menu-section px-2 sm:px-4 scroll-mt-[8.5rem] sm:scroll-mt-[9.75rem] lg:scroll-mt-[10.25rem]">
                <article class="menu-sheet{{ $sheetMod }}">
                    <div class="menu-sheet-grid">
                        <div>
                            <div class="flex items-start justify-between gap-4 mb-5">
                                <div>
                                    <img src="{{ $siteLogo }}" alt="Welcome 2 Kigali Expats Club" class="h-[5.6rem] md:h-[7rem] w-auto object-contain">
                                </div>
                                <div class="text-right lg:hidden pt-1">
                                    <h2 class="font-serif text-[1.55rem] leading-tight text-brand-gold">{{ \App\Support\SiteI18n::category($group['name']) }}</h2>
                                    <p class="text-white/80 text-[10px] tracking-[0.22em] uppercase mt-1">{{ \App\Support\SiteI18n::tagline($group['name']) }}</p>
                                </div>
                            </div>

                            @if (count($group['items']) === 0)
                                <p class="text-white/60 italic py-8">{{ __('site.menu.food_empty') }}</p>
                            @else
                                @foreach ($buckets as $bucket)
                                    @if ($bucket['title'] && count($bucket['items']))
                                        <p class="menu-cat">{{ \App\Support\SiteI18n::bucket($bucket['title']) }}</p>
                                    @endif
                                    <div>
                                        @foreach ($bucket['items'] as $item)
                                            @php $itemKey = (string) $item['id']; @endphp
                                            <div>
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
                                                    <p class="menu-item-note px-0.5 -mt-0.5 mb-1.5">{{ implode(', ', array_map([\App\Support\SiteI18n::class, 'flavor'], $item['flavors'])) }}</p>
                                                    <div class="flex flex-wrap gap-1.5 pb-1">
                                                        @foreach ($item['flavors'] as $flavor)
                                                            <button type="button"
                                                                    class="px-2 py-1 text-[12px] tracking-wide uppercase border border-brand-gold/35 text-brand-gold/90 hover:bg-brand-gold hover:text-black transition-colors"
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

                        <div class="menu-visual">
                            <div class="menu-visual-copy hidden lg:block">
                                <h2 class="font-serif text-[2.35rem] xl:text-[2.7rem] leading-[1.05] text-brand-gold">{{ \App\Support\SiteI18n::category($group['name']) }}</h2>
                                <div class="w-24 h-px bg-brand-gold/75 ml-auto my-2.5"></div>
                                <p class="text-white/90 text-[11px] tracking-[0.26em] uppercase">{{ \App\Support\SiteI18n::tagline($group['name']) }}</p>
                                @if (\Illuminate\Support\Facades\Lang::has('menu.blurbs.'.$group['name']))
                                    <p class="text-white/55 text-sm mt-2 leading-snug max-w-md ml-auto">{{ trans('menu.blurbs.'.$group['name']) }}</p>
                                @endif
                            </div>
                            <figure class="menu-photo-wrap">
                                <img src="{{ $group['image'] }}" alt="" class="menu-photo">
                            </figure>
                        </div>
                    </div>

                    <div class="menu-sheet-foot relative mt-5 pt-3 flex items-center justify-between text-[10px] tracking-[0.22em] uppercase text-white/65">
                        <span class="w-10"></span>
                        <span>{{ __('site.menu.prices') }}</span>
                        <span class="border border-brand-gold/60 text-brand-gold px-2 py-1">{{ str_pad((string) $pageNo, 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                </article>
            </section>
        @endforeach
    @endif

    <div x-show="toast" x-cloak x-transition
         class="fixed bottom-6 left-6 z-50 max-w-sm border border-brand-gold bg-black/95 px-4 py-3 shadow-2xl">
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
