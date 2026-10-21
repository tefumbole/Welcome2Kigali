<div class="mt-6">
    @include('beyond.partials.review-summary')
    <a href="{{ url('/reviews') }}{{ !empty($reference) ? '?ref='.urlencode($reference) : '' }}"
       class="inline-flex items-center justify-center gap-2 bg-brand-gold text-black font-bold px-5 py-3 rounded-full mt-4">
        {{ __('site.reviews.add') }}
    </a>
</div>
