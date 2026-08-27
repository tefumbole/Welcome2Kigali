@extends('beyond.layout')

@section('title', __('site.checkout.title'))
@section('meta_description', __('site.menu.meta'))
@section('body_class', 'bg-black')

@section('content')
<section class="bg-black text-white min-h-[70vh] py-12"
         x-data="{
            service: 'Dine in',
            takeawayFee: {{ (int) $takeawayFee }},
            subtotal: {{ (float) $total }},
            get fee() { return this.service === 'Take away' ? this.takeawayFee : 0; },
            get total() { return this.subtotal + this.fee; }
         }">
    <div class="max-w-5xl mx-auto px-4 grid lg:grid-cols-2 gap-10">
        <div>
            <h1 class="font-serif text-4xl text-brand-gold mb-8">{{ __('site.checkout.title') }}</h1>

            @if (session('not_permitted'))
                <p class="mb-4 text-red-300">{{ session('not_permitted') }}</p>
            @endif

            <form method="post" action="{{ route('order') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[11px] tracking-[0.2em] uppercase text-brand-gold mb-2">{{ __('site.checkout.name') }}</label>
                    <input name="name" type="text" required
                           value="{{ optional(auth()->user())->name }}"
                           class="w-full bg-black border border-brand-gold/40 text-white px-4 py-3 focus:outline-none focus:border-brand-gold">
                </div>
                <div>
                    <label class="block text-[11px] tracking-[0.2em] uppercase text-brand-gold mb-2">{{ __('site.checkout.phone') }}</label>
                    <input name="phone" type="text" required
                           value="{{ optional(auth()->user())->phone }}"
                           placeholder="2507..."
                           class="w-full bg-black border border-brand-gold/40 text-white px-4 py-3 focus:outline-none focus:border-brand-gold">
                </div>
                <div>
                    <p class="text-[11px] tracking-[0.2em] uppercase text-brand-gold mb-3">{{ __('site.checkout.service') }}</p>
                    <input type="hidden" name="address" value="Dine in" x-bind:value="service">
                    <div class="grid sm:grid-cols-2 gap-3">
                        <button type="button"
                                @click="service = 'Dine in'"
                                :class="service === 'Dine in' ? 'border-brand-gold bg-brand-gold/15' : 'border-brand-gold/35 hover:border-brand-gold/70'"
                                class="text-left border px-4 py-4 transition-colors">
                            <span class="flex items-center gap-2 font-serif text-lg text-[#F3E6C8]">
                                <i data-lucide="utensils" class="w-5 h-5 text-brand-gold"></i>
                                {{ __('site.checkout.dine_in') }}
                            </span>
                            <span class="block mt-1 text-sm text-white/55">{{ __('site.checkout.dine_in_note') }}</span>
                        </button>
                        <button type="button"
                                @click="service = 'Take away'"
                                :class="service === 'Take away' ? 'border-brand-gold bg-brand-gold/15' : 'border-brand-gold/35 hover:border-brand-gold/70'"
                                class="text-left border px-4 py-4 transition-colors">
                            <span class="flex items-center justify-between gap-2 font-serif text-lg text-[#F3E6C8]">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="shopping-bag" class="w-5 h-5 text-brand-gold"></i>
                                    {{ __('site.checkout.take_away') }}
                                </span>
                                <span class="text-brand-gold text-sm whitespace-nowrap">+{{ number_format($takeawayFee) }}</span>
                            </span>
                            <span class="block mt-1 text-sm text-white/55">{{ __('site.checkout.take_away_note') }}</span>
                        </button>
                    </div>
                    <input name="email" type="hidden">
                    <input name="city" type="hidden">
                    <input name="state" type="hidden">
                </div>
                <div>
                    <p class="text-[11px] tracking-[0.2em] uppercase text-brand-gold mb-2">{{ __('site.checkout.payment') }}</p>
                    <label class="flex items-center gap-2 mb-2">
                        <input type="radio" name="payment_method" value="COD" required> {{ __('site.checkout.cash') }}
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="payment_method" value="MTN"> {{ __('site.checkout.momo') }}
                    </label>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 pt-4">
                    <a href="{{ url('/cart') }}" class="text-center border border-brand-gold text-brand-gold px-6 py-3 text-sm tracking-[0.2em] uppercase">{{ __('site.checkout.return') }}</a>
                    <button type="submit" class="border border-brand-gold bg-brand-gold text-black px-6 py-3 text-sm tracking-[0.2em] uppercase">{{ __('site.checkout.place') }}</button>
                </div>
            </form>
        </div>

        <div>
            <h2 class="font-serif text-2xl text-brand-gold mb-4">{{ __('site.checkout.order') }}</h2>
            <div class="border border-brand-gold/40">
                @foreach ($cart as $item)
                    <div class="flex justify-between gap-4 px-5 py-4 border-b border-brand-gold/20">
                        <div>
                            <p class="font-serif text-[#F3E6C8]">{{ \App\Support\SiteI18n::cartName($item['name']) }}</p>
                            <p class="text-white/50 text-sm">× {{ (int) $item['quantity'] }}</p>
                        </div>
                        <p class="text-brand-gold whitespace-nowrap">{{ number_format(((float) $item['price']) * ((int) $item['quantity'])) }} FRW</p>
                    </div>
                @endforeach
                <div class="flex justify-between gap-4 px-5 py-4 border-b border-brand-gold/20 text-sm">
                    <p class="text-white/60">{{ __('site.checkout.subtotal') }}</p>
                    <p class="text-white/80">{{ number_format($total) }} FRW</p>
                </div>
                <div class="flex justify-between gap-4 px-5 py-4" x-show="fee > 0" x-cloak>
                    <p class="text-white/60">{{ __('site.checkout.take_away_fee') }}</p>
                    <p class="text-brand-gold whitespace-nowrap" x-text="fee.toLocaleString() + ' FRW'"></p>
                </div>
            </div>
            <div class="mt-6 flex justify-between items-center">
                <p class="text-white/60">{{ (int) $count }} {{ (int) $count === 1 ? __('site.cart.item') : __('site.cart.items') }} · {{ __('site.checkout.tax') }}</p>
                <p class="font-serif text-3xl text-brand-gold" x-text="total.toLocaleString() + ' FRW'">{{ number_format($total) }} FRW</p>
            </div>
        </div>
    </div>
</section>
@endsection
