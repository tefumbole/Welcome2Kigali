@php
    $path = request()->path();
    $settingsTabs = [
        ['key' => 'letterhead', 'label' => 'Brand & letterhead', 'url' => route('setting.general'), 'match' => 'setting/general_setting'],
        ['key' => 'units', 'label' => 'Units', 'url' => route('unit.index'), 'match' => 'unit'],
        ['key' => 'brands', 'label' => 'Brands', 'url' => route('brand.index'), 'match' => 'brand'],
        ['key' => 'categories', 'label' => 'Categories', 'url' => route('category.index'), 'match' => 'category'],
        ['key' => 'currency', 'label' => 'Currency', 'url' => route('currency.index'), 'match' => 'currency'],
        ['key' => 'tax', 'label' => 'Tax', 'url' => route('tax.index'), 'match' => 'tax'],
    ];
@endphp
<div class="site-content-tabs-nav mb-3" style="display:flex;flex-wrap:wrap;gap:8px;">
    @foreach($settingsTabs as $t)
        @php
            $active = $t['match'] === 'setting/general_setting'
                ? strpos($path, 'setting/general_setting') !== false
                : preg_match('#^'.preg_quote($t['match'], '#').'(/|$)#', $path);
        @endphp
        <a class="beyond-module-tab tone-blue {{ $active ? 'is-active' : '' }}" href="{{ $t['url'] }}">
            {{ $t['label'] }}
        </a>
    @endforeach
</div>
