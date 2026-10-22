@extends('beyond.layout')
@section('title', __('site.services.'.$service))
@section('meta_description', __('site.services.'.$service.'_body'))
@section('body_class', 'bg-[#0A0A0A]')

@push('head')
<style>
    .svc-cats { scrollbar-width: none; -webkit-overflow-scrolling: touch; }
    .svc-cats::-webkit-scrollbar { display: none; }
    .svc-cart-bar {
        position: fixed; left: .75rem; right: 4.75rem; bottom: .85rem; z-index: 45;
        display: flex; align-items: center; justify-content: space-between; gap: .75rem;
        background: #C5A059; color: #0A0A0A; border-radius: 999px;
        padding: .7rem 1rem .7rem 1.15rem; font-weight: 700; min-height: 3.15rem;
        box-shadow: 0 10px 30px rgba(0,0,0,.45);
    }
    @media (min-width: 1024px) { .svc-cart-bar { left: auto; right: 1.5rem; min-width: 18rem; } }
</style>
@endpush

@section('content')
@php
    $currentName = $current ? \App\Support\SiteI18n::category($current['name']) : '';
@endphp
<div x-data="w2kServiceCart({{ (int) $cartCount }})" class="bg-[#0A0A0A] text-white min-h-[70vh] pt-8 pb-28 px-4">
    <div class="max-w-5xl mx-auto">
        <a href="{{ route('beyond.services') }}" class="inline-flex items-center gap-1 text-xs uppercase tracking-[0.18em] text-brand-gold/90 hover:text-brand-gold">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> {{ __('site.services.all_services') }}
        </a>
        <div class="flex items-center gap-3 mt-4">
            <span class="w-12 h-12 rounded-full border border-brand-gold/60 flex items-center justify-center text-brand-gold shrink-0">
                <i data-lucide="{{ $meta['icon'] }}" class="w-6 h-6"></i>
            </span>
            <div>
                <h1 class="font-serif text-2xl md:text-3xl text-white m-0">{{ __('site.services.'.$service) }}</h1>
                <p class="text-white/65 text-sm m-0 mt-1">{{ __('site.services.'.$service.'_body') }}</p>
            </div>
        </div>

        @if (!empty($breadcrumb) && count($breadcrumb) > 1)
            <nav class="mt-4 flex flex-wrap items-center gap-2 text-xs text-white/55">
                <a href="{{ route('beyond.services.show', $service) }}" class="hover:text-brand-gold">{{ __('site.services.categories') }}</a>
                @foreach ($breadcrumb as $crumb)
                    <span class="text-brand-gold/50">/</span>
                    @if ($loop->last)
                        <span class="text-brand-gold">{{ \App\Support\SiteI18n::category($crumb['name']) }}</span>
                    @else
                        <a href="{{ route('beyond.services.show', [$service, $crumb['id']]) }}" class="hover:text-brand-gold">{{ \App\Support\SiteI18n::category($crumb['name']) }}</a>
                    @endif
                @endforeach
            </nav>
        @endif

        @if ($parents->isEmpty())
            <div class="mt-10 rounded-2xl border border-brand-gold/30 bg-white/[0.03] p-8 text-center">
                <p class="text-white/70 m-0">{{ __('site.services.no_categories') }}</p>
            </div>
        @else
            <p class="text-[10px] tracking-[0.2em] uppercase text-brand-gold/80 mt-8 mb-3">
                {{ ($current && ! empty($current['has_children'])) ? __('site.services.subcategories') : __('site.services.parent_categories') }}
            </p>
            <div class="svc-cats flex md:grid md:grid-cols-4 lg:grid-cols-5 gap-3 overflow-x-auto pb-1">
                @foreach (($subcategories->isNotEmpty() ? $subcategories : $categories) as $cat)
                    @php $active = $current && $current['id'] === $cat['id']; @endphp
                    <a href="{{ route('beyond.services.show', [$service, $cat['id']]) }}"
                       class="shrink-0 w-32 md:w-auto rounded-2xl border px-3 py-4 flex flex-col items-center text-center gap-2 transition-colors {{ $active ? 'border-brand-gold bg-brand-gold/15' : 'border-brand-gold/25 bg-white/[0.03] hover:border-brand-gold/70' }}">
                        <span class="w-12 h-12 rounded-full flex items-center justify-center overflow-hidden {{ $active ? 'bg-brand-gold text-[#0A0A0A]' : 'border border-brand-gold/50 text-brand-gold' }}">
                            @if ($cat['image'])
                                <img src="{{ $cat['image'] }}" alt="" class="w-full h-full object-cover">
                            @else
                                <i data-lucide="{{ $cat['icon'] }}" class="w-6 h-6"></i>
                            @endif
                        </span>
                        <span class="text-sm font-semibold leading-tight {{ $active ? 'text-brand-gold' : 'text-white/90' }}">{{ \App\Support\SiteI18n::category($cat['name']) }}</span>
                        <span class="text-[11px] text-white/45">{{ trans_choice('site.services.items_count', $cat['count'], ['count' => $cat['count']]) }}</span>
                        @if (!empty($cat['has_children']))
                            <span class="text-[10px] uppercase tracking-wider text-brand-gold/70">{{ __('site.services.subcategories') }}</span>
                        @endif
                    </a>
                @endforeach
            </div>

            @if ($current && $subcategories->isNotEmpty() && $items->isEmpty())
                <p class="text-white/55 text-sm mt-6 m-0">{{ __('site.services.choose_subcategory') }}</p>
            @endif

            @if ($current && $items->isNotEmpty())
                <section class="mt-8 rounded-2xl border border-brand-gold/30 bg-white/[0.03] overflow-hidden">
                    <div class="flex items-center gap-3 px-5 py-4 border-b border-brand-gold/20">
                        @if (!empty($current['image']))
                            <img src="{{ $current['image'] }}" alt="" class="w-10 h-10 rounded-full object-cover border border-brand-gold/40">
                        @else
                            <i data-lucide="{{ $current['icon'] }}" class="w-5 h-5 text-brand-gold"></i>
                        @endif
                        <h2 class="text-lg font-bold text-white m-0">{{ $currentName }}</h2>
                    </div>
                    <ul class="divide-y divide-white/10 m-0 p-0 list-none">
                        @foreach ($items as $item)
                            <li class="px-4 sm:px-5 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($item['image'])
                                        <button type="button" class="shrink-0 p-0 border-0 bg-transparent" @click="photo = {src: {{ json_encode($item['image']) }}, name: {{ json_encode(\App\Support\SiteI18n::item($item['name'])) }}}">
                                            <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="w-20 h-20 sm:w-24 sm:h-24 rounded-xl object-cover bg-white/5 border border-brand-gold/20" loading="lazy">
                                        </button>
                                    @else
                                        <span class="w-20 h-20 sm:w-24 sm:h-24 rounded-xl bg-white/5 flex items-center justify-center text-brand-gold/70 shrink-0">
                                            <i data-lucide="{{ $item['icon'] ?? $current['icon'] }}" class="w-8 h-8"></i>
                                        </span>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <p class="text-white font-semibold m-0 leading-snug">{{ \App\Support\SiteI18n::item($item['name']) }}</p>
                                        @if ($item['details'] !== '')
                                            <p class="text-white/55 text-xs m-0 mt-0.5 line-clamp-2">{{ \App\Support\SiteI18n::details($item['details']) }}</p>
                                        @endif
                                        <p class="text-brand-gold font-bold m-0 mt-1 tabular-nums">{{ number_format($item['price']) }} RWF</p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <button type="button" class="w-9 h-9 rounded-full border border-brand-gold/60 text-brand-gold text-lg leading-none" @click="dec({{ (int) $item['id'] }})" aria-label="−">−</button>
                                        <span class="w-6 text-center tabular-nums" x-text="qtyOf({{ (int) $item['id'] }})">1</span>
                                        <button type="button" class="w-9 h-9 rounded-full border border-brand-gold/60 text-brand-gold text-lg leading-none" @click="inc({{ (int) $item['id'] }})" aria-label="+">+</button>
                                        <button type="button" class="ml-1 rounded-full bg-brand-gold text-[#0A0A0A] text-[11px] font-bold tracking-[0.12em] uppercase px-3 sm:px-4 h-9 disabled:opacity-50"
                                                :disabled="busy"
                                                @click="add({{ (int) $item['id'] }}, {{ json_encode(\App\Support\SiteI18n::item($item['name'])) }})">{{ __('site.menu.add') }}</button>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @elseif ($current && empty($current['has_children']))
                <p class="text-white/55 italic mt-8 m-0">{{ __('site.services.no_items') }}</p>
            @endif
        @endif
    </div>

    <a href="{{ url('/cart') }}" class="svc-cart-bar" x-show="cartCount > 0" x-cloak>
        <span>{{ __('site.menu.your_order') }} <span x-text="'(' + cartCount + ')'"></span></span>
        <span class="uppercase tracking-widest text-xs">{{ __('site.menu.view_cart') }} →</span>
    </a>

    <div x-show="photo" x-cloak x-transition.opacity class="fixed inset-0 z-[60] bg-black/85 flex items-center justify-center p-6" @click="photo = null" @keydown.escape.window="photo = null">
        <figure class="m-0 text-center" @click.stop>
            <img :src="photo && photo.src" :alt="photo && photo.name" class="max-w-[90vw] max-h-[70vh] w-80 rounded-2xl object-cover border border-brand-gold/40">
            <figcaption class="text-white mt-3 font-semibold" x-text="photo && photo.name"></figcaption>
            <button type="button" class="mt-4 text-xs uppercase tracking-[0.18em] text-brand-gold" @click="photo = null">{{ __('site.services.close') }}</button>
        </figure>
    </div>

    <div x-show="toast" x-cloak x-transition
         class="fixed bottom-24 left-4 z-50 max-w-sm rounded-2xl border border-brand-gold bg-black/95 px-4 py-3 shadow-2xl">
        <p class="text-brand-gold text-xs tracking-[0.2em] uppercase m-0">{{ __('site.menu.added') }}</p>
        <p class="text-white text-sm mt-1 mb-0" x-text="toast"></p>
    </div>
</div>
@endsection

@push('scripts')
<script>
function w2kServiceCart(initialCount) {
    return {
        busy: false,
        toast: '',
        photo: null,
        cartCount: initialCount || 0,
        qty: {},
        qtyOf(id) { return this.qty[id] || 1; },
        inc(id) { this.qty = Object.assign({}, this.qty, { [id]: this.qtyOf(id) + 1 }); },
        dec(id) { this.qty = Object.assign({}, this.qty, { [id]: Math.max(1, this.qtyOf(id) - 1) }); },
        async add(id, name) {
            if (this.busy) return;
            this.busy = true;
            try {
                const qty = this.qtyOf(id);
                const res = await fetch('/addToCart?id=' + encodeURIComponent(id) + '&quantity=' + qty, {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await res.json().catch(function () { return {}; });
                this.cartCount = data.number != null ? data.number : (this.cartCount + qty);
                this.toast = name;
                setTimeout(() => { this.toast = ''; }, 2200);
                if (window.lucide) window.lucide.createIcons();
            } finally {
                this.busy = false;
            }
        }
    };
}
</script>
@endpush
