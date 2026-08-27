<a href="{{ \App\Support\SiteBrand::mapsUrl() }}" target="_blank" rel="noopener" class="block space-y-3 text-gray-600 hover:text-brand-gold">
    <p class="font-semibold text-gray-800">{{ \App\Support\SiteBrand::officeName() }}</p>
    @foreach (\App\Support\SiteBrand::officeLines() as $officeLine)
        <p>{{ $officeLine }}</p>
    @endforeach
    <p class="text-brand-gold text-xs font-semibold inline-flex items-center gap-1.5">
        <i data-lucide="map" class="w-3.5 h-3.5"></i>
        {{ __('site.contact.open_maps') }}
    </p>
</a>
<div class="mt-4 overflow-hidden rounded-lg border border-gray-100">
    <iframe
        title="{{ \App\Support\SiteBrand::officeName() }}"
        src="{{ \App\Support\SiteBrand::mapsEmbedUrl() }}"
        class="w-full h-40 border-0"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
        allowfullscreen></iframe>
</div>
