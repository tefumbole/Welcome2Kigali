<div class="mt-6">
    <a href="{{ url('/reviews') }}{{ !empty($reference) ? '?ref='.urlencode($reference) : '' }}"
       class="inline-flex items-center justify-center gap-2 bg-brand-gold text-black font-bold px-5 py-3 rounded-md">
        {{ __('site.reviews.cta') }}
    </a>
    <p class="text-sm text-gray-500 mt-2 mb-0">{{ __('site.reviews.cta_hint') }}</p>
</div>
