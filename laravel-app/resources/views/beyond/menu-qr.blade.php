@extends('beyond.layout')

@section('title', __('site.menu.qr_title'))
@section('hide_footer', '1')
@section('body_class', 'bg-black')

@push('head')
<style>
    @media print {
        header, .print-hide { display: none !important; }
        body { background: #fff !important; }
        .qr-card { box-shadow: none !important; border: 0 !important; }
    }
</style>
@endpush

@section('content')
<section class="min-h-[80vh] bg-black text-white flex items-center justify-center px-4 py-12">
    <div class="qr-card w-full max-w-md bg-[#111] border border-brand-gold/40 rounded-3xl p-8 text-center shadow-2xl">
        <p class="text-[11px] tracking-[0.28em] uppercase text-brand-gold">Welcome 2 Kigali</p>
        <h1 class="font-serif text-3xl text-brand-gold mt-2">{{ __('site.menu.qr_title') }}</h1>
        <p class="text-white/70 text-sm mt-2">{{ __('site.menu.qr_sub') }}</p>
        <div class="mt-6 bg-white rounded-2xl p-4 inline-block">
            <img src="{{ $qr }}" alt="Menu QR" class="w-64 h-64 object-contain">
        </div>
        <p class="mt-4 text-xs text-white/50 break-all">{{ $menuUrl }}</p>
        <div class="print-hide mt-6 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ url('/menu/qr.png') }}" class="border border-brand-gold bg-brand-gold text-black font-bold px-5 py-3 rounded-full text-sm uppercase tracking-widest">{{ __('site.menu.qr_download') }}</a>
            <button type="button" onclick="window.print()" class="border border-brand-gold text-brand-gold font-bold px-5 py-3 rounded-full text-sm uppercase tracking-widest">{{ __('site.menu.qr_print') }}</button>
        </div>
    </div>
</section>
@endsection
