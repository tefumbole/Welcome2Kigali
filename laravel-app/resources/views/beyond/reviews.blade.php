@extends('beyond.layout')
@section('title', __('site.reviews.title'))
@section('meta_description', __('site.reviews.meta'))
@section('body_class', 'bg-[#0A0A0A]')

@push('head')
<style>
    .review-score input { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .review-score span {
        display: inline-flex; align-items: center; justify-content: center;
        width: 2.75rem; height: 2.75rem; border-radius: 999px;
        border: 1px solid rgba(197,160,89,.55); color: #C5A059; font-weight: 700;
    }
    .review-score input:checked + span,
    .review-score input:focus + span { background: #C5A059; color: #0A0A0A; }
</style>
@endpush

@section('content')
<div class="bg-[#0A0A0A] text-white min-h-[70vh] py-10 px-4">
    <div class="max-w-xl mx-auto">
        <p class="text-[11px] tracking-[0.22em] uppercase text-brand-gold m-0">Welcome 2 Kigali</p>
        <h1 class="font-serif text-3xl text-white mt-2">{{ \App\Support\SiteContent::text('reviews.heading', __('site.reviews.heading')) }}</h1>
        <p class="text-white/70 text-sm mt-2">{{ \App\Support\SiteContent::text('reviews.intro', __('site.reviews.intro')) }}</p>

        @if ($saved)
            <div class="mt-8 rounded-2xl border border-brand-gold/40 bg-white/5 p-6 text-center">
                <h2 class="text-xl font-bold text-brand-gold m-0">{{ \App\Support\SiteContent::text('reviews.thanks_title', __('site.reviews.thanks_title')) }}</h2>
                <p class="text-white/80 mt-2 mb-0">{{ \App\Support\SiteContent::text('reviews.thanks_body', __('site.reviews.thanks_body')) }}</p>
                <a href="{{ url('/') }}" class="inline-block mt-5 text-sm font-semibold text-brand-gold underline">{{ __('site.register.return_home') }}</a>
            </div>
        @else
            @if ($errors->any())
                <div class="mt-6 bg-red-950/40 border border-red-400/40 text-red-200 rounded-xl px-4 py-3 text-sm">
                    <ul class="list-disc pl-5 mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('beyond.reviews.store') }}" class="mt-8 space-y-5">
                @csrf
                <input type="hidden" name="reference" value="{{ old('reference', $reference) }}">

                @foreach ([
                    'cleanliness' => __('site.reviews.cleanliness'),
                    'on_time' => __('site.reviews.on_time'),
                    'service' => __('site.reviews.service'),
                    'food_quality' => __('site.reviews.food'),
                ] as $field => $label)
                    <fieldset class="rounded-xl border border-white/10 bg-white/5 p-4">
                        <legend class="px-1 text-sm font-semibold text-brand-gold">{{ $label }}</legend>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @for ($score = 1; $score <= 5; $score++)
                                <label class="review-score cursor-pointer">
                                    <input type="radio" name="{{ $field }}" value="{{ $score }}" @if((int) old($field) === $score) checked @endif required>
                                    <span>{{ $score }}</span>
                                </label>
                            @endfor
                        </div>
                        <p class="text-[11px] text-white/45 mt-2 mb-0">1 {{ __('site.reviews.low') }} · 5 {{ __('site.reviews.high') }}</p>
                    </fieldset>
                @endforeach

                <div>
                    <label class="text-sm font-semibold text-white/80">{{ __('site.reviews.name') }}</label>
                    <input name="guest_name" value="{{ old('guest_name') }}" maxlength="80" class="w-full mt-1 rounded-md bg-black border border-white/15 px-3 py-2 text-white">
                </div>
                <div>
                    <label class="text-sm font-semibold text-white/80">{{ __('site.reviews.comment') }}</label>
                    <textarea name="comment" rows="3" maxlength="1000" class="w-full mt-1 rounded-md bg-black border border-white/15 px-3 py-2 text-white">{{ old('comment') }}</textarea>
                </div>
                <button type="submit" class="w-full bg-brand-gold text-black font-bold py-3 rounded-md">{{ __('site.reviews.submit') }}</button>
            </form>
        @endif
    </div>
</div>
@endsection
