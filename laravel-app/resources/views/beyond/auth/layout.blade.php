@extends('beyond.layout')

@section('title', $title ?? \App\Support\SiteBrand::siteTitle())

@push('head')
<style>
    .beyond-logo-plate {
        width: 100%;
        max-width: 20.5rem;
        margin: 0 auto 1.15rem;
        background: #0A0A0A;
        border: 1px solid #C5A059;
        border-radius: 1rem;
        padding: 1.05rem 1.2rem .95rem;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 28px rgba(10, 10, 10, .28), 0 0 0 1px rgba(197, 160, 89, .22);
    }
    .beyond-logo-plate img {
        display: block;
        width: 100%;
        height: auto;
        max-height: 7.5rem;
        object-fit: contain;
        object-position: center;
    }
</style>
@endpush

@section('content')
<div class="min-h-[80vh] bg-gradient-to-br from-brand-blue to-[#001f42] flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl overflow-hidden">
        <div class="pt-8 pb-4 px-8 text-center">
            <div class="beyond-logo-plate">
                <img src="{{ \App\Support\SiteBrand::logoUrl($general_setting ?? null) }}" alt="{{ \App\Support\SiteBrand::siteTitle($general_setting ?? null) }}">
            </div>
            @if (!empty($header))
                {!! $header !!}
            @endif
            <div class="mt-4 h-1 w-24 mx-auto rounded-full bg-brand-blue"></div>
        </div>
        <div class="px-8 pb-8 pt-2">
            @if (session('success'))
                <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif
            @yield('auth_body')
            <div class="mt-6 pt-4 border-t border-gray-100 text-center text-xs text-gray-500 leading-relaxed">
                <div class="font-bold text-brand-blue tracking-wide">{{ \App\Support\AppVersion::bcl() }}</div>
                <div class="mt-1">{{ __('site.footer.developed') }} <span class="font-semibold text-gray-700">Sr. Engr. Tefu R. Mbole</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
