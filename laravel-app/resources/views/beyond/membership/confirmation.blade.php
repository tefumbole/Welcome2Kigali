@extends('beyond.layout')
@section('title', __('site.membership.confirm_title'))
@section('content')
<div class="min-h-screen bg-gray-50 py-16">
    <div class="max-w-xl mx-auto bg-white rounded-xl shadow-xl border p-8 text-center">
        <h1 class="text-3xl font-extrabold text-brand-blue">{{ __('site.membership.complete') }}</h1>
        <p class="mt-3 text-gray-600">{{ __('site.membership.thank_you', ['name' => $application->full_name]) }}</p>
        <p class="mt-4 font-mono text-lg font-bold">{{ $application->reference }}</p>
        <p class="text-sm text-gray-500 mt-4">{{ __('site.membership.next') }}</p>
        <a href="{{ url('/') }}" class="inline-block mt-6 bg-brand-blue text-white font-bold px-6 py-3 rounded-md">{{ __('site.register.return_home') }}</a>
    </div>
</div>
@endsection
