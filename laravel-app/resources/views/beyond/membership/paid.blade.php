@extends('beyond.layout')
@section('title', __('site.membership.paid_title'))
@section('content')
<div class="min-h-screen bg-gray-50 py-16">
    <div class="max-w-xl mx-auto bg-white rounded-xl shadow p-8 text-center">
        <h1 class="text-3xl font-extrabold text-brand-blue">{{ __('site.membership.paid_title') }}</h1>
        @if($membership)
            <p class="mt-3 font-mono text-lg">{{ $membership->number }}</p>
            <p>{{ $membership->status }} · {{ __('site.membership.expires') }} {{ optional($membership->expires_at)->toFormattedDateString() }}</p>
            <a class="inline-block mt-6 bg-brand-blue text-white font-bold px-6 py-3 rounded-md" href="{{ route('membership.account') }}">{{ __('site.membership.account_title') }}</a>
        @endif
    </div>
</div>
@endsection
