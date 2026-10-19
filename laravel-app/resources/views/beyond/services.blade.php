@extends('beyond.layout')
@section('title', __('site.services.title'))
@section('meta_description', __('site.services.meta'))
@section('body_class', 'bg-[#0A0A0A]')

@php
    $wa = \App\Support\SiteBrand::phoneWhatsAppDigits();
@endphp

@section('content')
<div class="bg-[#0A0A0A] text-white min-h-[70vh] py-10 px-4">
    <div class="max-w-5xl mx-auto">
        <p class="text-[11px] tracking-[0.22em] uppercase text-brand-gold m-0">{{ __('site.services.kicker') }}</p>
        <h1 class="font-serif text-3xl md:text-4xl text-white mt-2">{{ __('site.services.heading') }}</h1>
        <p class="text-white/70 text-sm mt-2 max-w-2xl">{{ __('site.services.intro') }}</p>

        <div class="mt-8 grid gap-5 md:grid-cols-2">
            @foreach (\App\Support\ServiceCatalog::SERVICES as $key => $svc)
                @php
                    $img = \App\Support\ServiceCatalog::imageUrl($key);
                    $cats = \App\Support\ServiceCatalog::categories($key);
                    $showUrl = route('beyond.services.show', $key);
                @endphp
                <article class="rounded-2xl border {{ !empty($svc['priority']) ? 'border-brand-gold md:col-span-2 bg-gradient-to-br from-[#1a140b] to-[#0A0A0A]' : 'border-brand-gold/30 bg-white/[0.03]' }} overflow-hidden flex flex-col">
                    @if ($img)
                        <a href="{{ $showUrl }}"><img src="{{ $img }}" alt="" class="w-full h-40 object-cover" loading="lazy"></a>
                    @endif
                    <div class="p-6 flex-1 flex flex-col">
                        <div class="flex items-center gap-3">
                            <span class="w-11 h-11 rounded-full border border-brand-gold/60 flex items-center justify-center text-brand-gold shrink-0">
                                <i data-lucide="{{ $svc['icon'] }}" class="w-5 h-5"></i>
                            </span>
                            <h2 class="text-xl font-bold text-white m-0"><a href="{{ $showUrl }}" class="hover:text-brand-gold">{{ __('site.services.'.$key) }}</a></h2>
                            @if (!empty($svc['priority']))
                                <span class="ml-auto text-[10px] tracking-[0.18em] uppercase font-bold bg-brand-gold text-[#0A0A0A] rounded-full px-3 py-1">{{ __('site.services.priority') }}</span>
                            @endif
                        </div>
                        <p class="text-white/75 text-sm mt-3 mb-0">{{ __('site.services.'.$key.'_body') }}</p>

                        <p class="text-[10px] tracking-[0.2em] uppercase text-brand-gold/80 mt-5 mb-2">{{ __('site.services.categories') }}</p>
                        @if ($cats->isEmpty())
                            <p class="text-white/50 text-sm italic m-0 flex-1">{{ __('site.services.no_categories') }}</p>
                        @else
                            <div class="flex flex-wrap gap-2 flex-1 content-start">
                                @foreach ($cats as $cat)
                                    <a href="{{ route('beyond.services.show', [$key, $cat['id']]) }}"
                                       class="inline-flex items-center gap-2 rounded-full border border-brand-gold/40 hover:border-brand-gold hover:bg-brand-gold/10 px-3 py-1.5 text-sm text-white/90">
                                        @if ($cat['image'])
                                            <img src="{{ $cat['image'] }}" alt="" class="w-5 h-5 rounded-full object-cover">
                                        @else
                                            <i data-lucide="{{ $cat['icon'] }}" class="w-4 h-4 text-brand-gold"></i>
                                        @endif
                                        <span>{{ \App\Support\SiteI18n::category($cat['name']) }}</span>
                                        <span class="text-[11px] text-white/45">{{ $cat['count'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-5 flex flex-wrap gap-3">
                            @if ($cats->isNotEmpty())
                                <a href="{{ $showUrl }}" class="inline-flex items-center gap-2 text-xs font-bold tracking-[0.14em] uppercase bg-brand-gold text-[#0A0A0A] rounded-full px-4 py-2">{{ __('site.services.cta_browse') }}</a>
                            @endif
                            @if ($wa)
                                <a href="https://wa.me/{{ $wa }}?text={{ rawurlencode(__('site.services.'.$key)) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-xs font-bold tracking-[0.14em] uppercase border border-brand-gold/60 text-brand-gold rounded-full px-4 py-2">{{ __('site.services.cta_contact') }}</a>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</div>
@endsection
