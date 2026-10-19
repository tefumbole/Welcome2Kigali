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
    $wa = \App\Support\SiteBrand::phoneWhatsAppDigits();
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

        @if ($categories->isEmpty())
            <div class="mt-10 rounded-2xl border border-brand-gold/30 bg-white/[0.03] p-8 text-center">
                <p class="text-white/70 m-0">{{ __('site.services.no_categories') }}</p>
                @if ($wa)
                    <a href="https://wa.me/{{ $wa }}?text={{ rawurlencode(__('site.services.'.$service)) }}" target="_blank" rel="noopener" class="inline-flex mt-5 text-xs font-bold tracking-[0.14em] uppercase border border-brand-gold/60 text-brand-gold rounded-full px-4 py-2">{{ __('site.services.cta_contact') }}</a>
                @endif
            </div>
        @else
            <p class="text-[10px] tracking-[0.2em] uppercase text-brand-gold/80 mt-8 mb-3">{{ __('site.services.categories') }}</p>
            <div class="svc-cats flex md:grid md:grid-cols-4 lg:grid-cols-5 gap-3 overflow-x-auto pb-1">
                @foreach ($categories as $cat)
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
                    </a>
                @endforeach
            </div>

            @if ($current)
                <section class="mt-8 rounded-2xl border border-brand-gold/30 bg-white/[0.03] overflow-hidden">
                    <div class="flex items-center gap-3 px-5 py-4 border-b border-brand-gold/20">
                        <i data-lucide="{{ $current['icon'] }}" class="w-5 h-5 text-brand-gold"></i>
                        <h2 class="text-lg font-bold text-white m-0">{{ $currentName }}</h2>
                    </div>
                    @if ($items->isEmpty())
                        <p class="text-white/55 italic px-5 py-8 m-0">{{ __('site.services.no_items') }}</p>
                    @else
                        <ul class="divide-y divide-white/10 m-0 p-0 list-none">
                            @foreach ($items as $item)
                                <li class="px-4 sm:px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($item['image'])
                                            <img src="{{ $item['image'] }}" alt="" class="w-14 h-14 rounded-xl object-cover shrink-0 bg-white/5" loading="lazy">
                                        @else
                                            <span class="w-14 h-14 rounded-xl bg-white/5 flex items-center justify-center text-brand-gold/70 shrink-0">
                                                <i data-lucide="{{ $current['icon'] }}" class="w-6 h-6"></i>
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
                    @endif
                </section>
            @endif
        @endif
    </div>

    <a href="{{ url('/cart') }}" class="svc-cart-bar" x-show="cartCount > 0" x-cloak>
        <span>{{ __('site.menu.your_order') }} <span x-text="'(' + cartCount + ')'"></span></span>
        <span class="uppercase tracking-widest text-xs">{{ __('site.menu.view_cart') }} →</span>
    </a>

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
        cartCount: initialCount || 0,
        qty: {},
        qtyOf(id) { return this.qty[id] || 1; },
        inc(id) { this.qty = Object.assign({}, this.qty, { [id]: this.qtyOf(id) + 1 }); },
        dec(id) { this.qty = Object.assign({}, this.qty, { [id]: Math.max(1, this.qtyOf(id) - 1) }); },
        async add(id, name) {
            if (this.busy) return;
            this.busy = true;
            var quantity = this.qtyOf(id);
            try {
                const params = new URLSearchParams({ id: String(id), quantity: String(quantity) });
                const res = await fetch('{{ url('/addToCart') }}?' + params.toString(), {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.error || @json(__('site.menu.add_error')));
                this.cartCount = data.number || 0;
                this.toast = name + ' × ' + quantity;
                this.qty = Object.assign({}, this.qty, { [id]: 1 });
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
