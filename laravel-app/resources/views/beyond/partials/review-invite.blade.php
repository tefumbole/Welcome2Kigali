<div class="mt-6">
    <a href="{{ url('/reviews') }}{{ !empty($reference) ? '?ref='.urlencode($reference) : '' }}"
       class="inline-flex items-center justify-center gap-2 bg-brand-gold text-black font-bold px-5 py-3 rounded-full">
        {{ __('site.reviews.add') }}
    </a>
</div>
