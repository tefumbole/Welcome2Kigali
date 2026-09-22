@extends('beyond.layout')

@section('title', $title ?? \App\Support\SiteBrand::siteTitle())

@push('head')
<style>
    @keyframes beyondLogoSpin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    @keyframes beyondLogoGlow {
        0%, 100% {
            box-shadow:
                0 0 0 3px #C5A059,
                0 0 0 6px rgba(197, 160, 89, 0.28),
                0 0 22px rgba(197, 160, 89, 0.45);
        }
        50% {
            box-shadow:
                0 0 0 3px #E8C56B,
                0 0 0 7px rgba(232, 197, 107, 0.35),
                0 0 32px rgba(232, 197, 107, 0.6);
        }
    }
    .beyond-logo-spin-wrap {
        width: 7rem;
        height: 7rem;
        padding: 7px;
        box-sizing: border-box;
        border-radius: 9999px;
        background: #ffffff;
        border: 3px solid #C5A059;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: beyondLogoGlow 2.8s ease-in-out infinite;
    }
    .beyond-logo-spin {
        width: 122%;
        height: 122%;
        border-radius: 9999px;
        background: transparent;
        object-fit: contain;
        animation: beyondLogoSpin 6s linear infinite;
    }
    @media (prefers-reduced-motion: reduce) {
        .beyond-logo-spin, .beyond-logo-spin-wrap { animation: none; }
    }
</style>
@endpush

@section('content')
<div class="min-h-[80vh] bg-gradient-to-br from-brand-blue to-[#001f42] flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl overflow-hidden">
        <div class="pt-6 sm:pt-8 pb-4 px-5 sm:px-8 text-center">
            <div class="beyond-logo-spin-wrap mx-auto mb-4">
                <img src="{{ \App\Support\SiteBrand::logoUrl($general_setting ?? null) }}" alt="{{ \App\Support\SiteBrand::siteTitle($general_setting ?? null) }}"
                     class="beyond-logo-spin">
            </div>
            @if (!empty($header))
                {!! $header !!}
            @endif
            <div class="mt-4 h-1 w-24 mx-auto rounded-full bg-brand-blue"></div>
        </div>
        <div class="px-5 sm:px-8 pb-6 sm:pb-8 pt-2">
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
