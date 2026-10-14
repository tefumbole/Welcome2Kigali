@php
    $tab = $tab ?? '';
@endphp
<script>
    $("ul#laundry-module").siblings('a').attr('aria-expanded','true');
    $("ul#laundry-module").addClass("show");
    $("ul#laundry-module #laundry-{{ $tab }}-menu").addClass("active");
</script>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'received' ? 'active' : '' }}" href="{{ route('laundry.received') }}">Received</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'cleaning' ? 'active' : '' }}" href="{{ route('laundry.cleaning') }}">Cleaning</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'ready' ? 'active' : '' }}" href="{{ route('laundry.ready') }}">Ready for Collection</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'collect' ? 'active' : '' }}" href="{{ route('laundry.collect') }}">Collect</a>
    </li>
</ul>
