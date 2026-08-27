@extends('beyond.layout')
@section('title', __('site.membership.verify_title'))
@section('content')
<div class="min-h-screen bg-gray-50 py-16">
    <div class="max-w-lg mx-auto bg-white rounded-xl shadow-xl border p-8 text-center">
        @if($membership)
            <p class="text-brand-gold font-bold uppercase tracking-wider text-sm">{{ __('site.membership.verify_title') }}</p>
            <h1 class="text-3xl font-extrabold text-brand-blue mt-2">{{ optional($membership->customer)->name }}</h1>
            <p class="font-mono mt-2 text-lg">{{ $membership->number }}</p>
            <p class="mt-4"><span class="inline-block px-3 py-1 rounded-full bg-brand-blue text-white text-sm">{{ $membership->status }}</span></p>
            <p class="text-gray-600 mt-3">{{ __('site.membership.expires') }}: {{ optional($membership->expires_at)->toFormattedDateString() ?: '—' }}</p>
        @else
            <h1 class="text-2xl font-bold text-brand-blue">{{ __('site.membership.not_found') }}</h1>
        @endif
    </div>
</div>
@endsection
