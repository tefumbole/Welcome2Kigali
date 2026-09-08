@extends('beyond.layout')
@section('title', __('site.membership.id_scan_title'))
@section('hide_footer', '1')
@section('body_class', 'bg-black')
@section('content')
<section class="min-h-[70vh] bg-black text-white flex items-center justify-center px-4">
    <div class="max-w-md text-center">
        <h1 class="font-serif text-3xl text-brand-gold">{{ __('site.membership.id_scan_done') }}</h1>
        <p class="mt-3 text-white/70">{{ __('site.membership.id_scan_return') }}</p>
    </div>
</section>
@endsection
