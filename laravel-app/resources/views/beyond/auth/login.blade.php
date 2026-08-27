@extends('beyond.auth.layout')

@section('title', __('site.auth.title'))

@php
    $title = __('site.auth.title');
    $header = '<h1 class="text-2xl font-bold text-brand-blue">'.e(\App\Support\SiteBrand::siteTitle($general_setting ?? null)).'</h1><p class="text-brand-blue text-sm mt-1">'.e(__('site.auth.continue')).'</p>';
    $asCustomer = !empty($asCustomer);
    $signinQuery = [];
    if (request('redirect')) { $signinQuery['redirect'] = request('redirect'); }
    if ($asCustomer) { $signinQuery['as'] = 'customer'; }
    $signinUrl = url('/login'.(count($signinQuery) ? '?'.http_build_query($signinQuery) : ''));
@endphp

@section('auth_body')
@if($asCustomer)
    <div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 text-sm">
        {{ __('site.auth.customer_note') }}
        <a href="{{ url('/login'.(request('redirect') ? '?redirect='.urlencode(request('redirect')) : '')) }}" class="font-semibold underline">{{ __('site.auth.staff_instead') }}</a>
    </div>
@endif
<form method="POST" action="{{ url('/login') }}" class="space-y-5">
    @csrf
    @if($asCustomer)
        <input type="hidden" name="as" value="customer">
    @endif
    <div class="space-y-2">
        <label class="text-sm font-semibold text-gray-700">{{ __('site.auth.identifier') }}</label>
        <div class="relative">
            <i data-lucide="user" class="absolute left-3 top-3 h-4 w-4 text-gray-400"></i>
            <input type="text" name="identifier" value="{{ old('identifier', $prefill) }}" required
                   class="w-full pl-10 rounded-md border border-gray-200 px-3 py-2 focus:border-brand-blue outline-none"
                   placeholder="{{ __('site.auth.identifier_ph') }}" autocomplete="username">
        </div>
    </div>
    <div class="space-y-2">
        <label class="text-sm font-semibold text-gray-700">{{ __('site.auth.password') }}</label>
        <div class="relative">
            <i data-lucide="lock" class="absolute left-3 top-3 h-4 w-4 text-gray-400"></i>
            <input type="password" name="password" required
                   value="{{ $guestPassword ? 'system' : '' }}"
                   class="w-full pl-10 rounded-md border border-gray-200 px-3 py-2 focus:border-brand-blue outline-none"
                   placeholder="••••••••" autocomplete="current-password">
        </div>
    </div>
    <div class="flex items-center justify-end text-sm">
        <a href="{{ url('/forgot-password') }}" class="text-brand-light hover:text-brand-blue font-medium">{{ __('site.auth.forgot') }}</a>
    </div>
    @unless($asCustomer)
    <p class="text-center text-sm">
        <a href="{{ url('/staff-otp-login') }}" class="text-brand-blue font-semibold hover:underline">{{ __('site.auth.otp') }}</a>
    </p>
    @endunless
    <button type="submit" class="w-full bg-brand-blue hover:bg-brand-dark text-white font-bold py-3 rounded-md flex items-center justify-center gap-2">
        {{ __('site.auth.sign_in') }} <i data-lucide="arrow-right" class="w-4 h-4"></i>
    </button>
    @unless($asCustomer)
    <p class="text-center text-xs text-gray-500">
        {{ __('site.auth.same_email') }}
        <a href="{{ url('/login?as=customer'.(request('redirect') ? '&redirect='.urlencode(request('redirect')) : '')) }}" class="text-brand-gold font-semibold hover:underline">{{ __('site.auth.as_customer') }}</a>
    </p>
    @endunless
</form>
@endsection
