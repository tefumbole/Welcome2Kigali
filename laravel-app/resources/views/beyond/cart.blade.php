@extends('beyond.layout')

@section('title', __('site.cart.title'))
@section('meta_description', __('site.menu.meta'))
@section('body_class', 'bg-black')

@section('content')
<section class="bg-black text-white min-h-[70vh] py-12">
    <div class="max-w-4xl mx-auto px-4">
        <h1 class="font-serif text-4xl md:text-5xl text-brand-gold mb-8">{{ __('site.cart.title') }}</h1>

        @if (empty($cart))
            <div class="border border-brand-gold/40 px-6 py-12 text-center">
                <p class="text-white/70 mb-6">{{ __('site.cart.empty') }}</p>
                <a href="{{ url('/menu') }}" class="inline-block border border-brand-gold bg-brand-gold text-black px-6 py-2 text-sm tracking-[0.2em] uppercase">{{ __('site.cart.browse') }}</a>
            </div>
        @else
            <div class="border border-brand-gold/40">
                @foreach ($cart as $key => $item)
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 px-5 py-4 border-b border-brand-gold/20 last:border-b-0">
                        <div class="flex-1 min-w-0">
                            <p class="font-serif text-lg text-[#F3E6C8]">{{ \App\Support\SiteI18n::cartName($item['name']) }}</p>
                            <p class="text-brand-gold text-sm mt-1">{{ number_format($item['price']) }} FRW</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="button" class="w-8 h-8 border border-brand-gold/50 text-brand-gold" onclick="w2kCartAction('{{ url('/cart/minus') }}', {{ json_encode((string) $key) }})">−</button>
                            <span class="w-8 text-center">{{ (int) $item['quantity'] }}</span>
                            <button type="button" class="w-8 h-8 border border-brand-gold/50 text-brand-gold" onclick="w2kCartAction('{{ url('/cart/plus') }}', {{ json_encode((string) $key) }})">+</button>
                        </div>
                        <p class="font-serif text-brand-gold whitespace-nowrap sm:w-28 sm:text-right">
                            {{ number_format(((float) $item['price']) * ((int) $item['quantity'])) }} FRW
                        </p>
                        <button type="button" class="text-white/50 hover:text-red-400 text-xs uppercase tracking-widest" onclick="w2kCartAction('{{ url('/cart/delete') }}', {{ json_encode((string) $key) }})">{{ __('site.cart.remove') }}</button>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <p class="text-white/70">{{ (int) $count }} {{ (int) $count === 1 ? __('site.cart.item') : __('site.cart.items') }} · {{ __('site.cart.tax') }}</p>
                <p class="font-serif text-3xl text-brand-gold">{{ number_format($total) }} FRW</p>
            </div>
            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                <a href="{{ url('/menu') }}" class="inline-block text-center border border-brand-gold text-brand-gold px-6 py-3 text-sm tracking-[0.2em] uppercase hover:bg-brand-gold hover:text-black">{{ __('site.cart.continue') }}</a>
                <a href="{{ url('/checkout') }}" class="inline-block text-center border border-brand-gold bg-brand-gold text-black px-6 py-3 text-sm tracking-[0.2em] uppercase">{{ __('site.cart.checkout') }}</a>
            </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
function w2kCartAction(url, id) {
    fetch(url + (url.indexOf('?') >= 0 ? '&' : '?') + 'id=' + encodeURIComponent(id), {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function () { window.location.reload(); });
}
</script>
@endpush
