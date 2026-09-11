@extends('layout.main')

@section('content')
<style>
    .site-content-tabs-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 1.5rem;
    }
    .reorder-list .list-group-item {
        border-left: 4px solid #0b3f90;
        transition: background 0.15s ease;
    }
    .reorder-list .list-group-item:nth-child(6n+2) { border-left-color: #7b61ff; }
    .reorder-list .list-group-item:nth-child(6n+3) { border-left-color: #c6ab47; }
    .reorder-list .list-group-item:nth-child(6n+4) { border-left-color: #10b981; }
    .reorder-list .list-group-item:nth-child(6n+5) { border-left-color: #e91e8c; }
    .reorder-list .list-group-item:nth-child(6n+6) { border-left-color: #06b6d4; }
    .reorder-actions .btn { min-width: 34px; margin-left: 3px; }
    .reorder-actions .move-top,
    .reorder-actions .move-bottom { font-size: 14px; line-height: 1; }
    .reorder-list .list-group-item { cursor: grab; }
    .reorder-list .list-group-item.sortable-ghost {
        opacity: 0.45;
        border: 2px dashed #0b3f90;
        background: #eef4ff;
    }
    .reorder-list .list-group-item.sortable-drag { cursor: grabbing; box-shadow: 0 8px 20px rgba(11,63,144,0.18); }
    .reorder-drag-handle {
        color: #0b3f90;
        font-weight: 700;
        font-size: 12px;
        margin-right: 10px;
        user-select: none;
    }
</style>
<section class="forms">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-1"><i class="dripicons-web"></i> Site Content</h4>
                <p class="text-muted">Manage what appears on the site and in what order. Edits go live when you press Save.</p>

                @if(session('message'))
                    <div class="alert alert-success">{{ session('message') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                @php
                    $menuTabs = [
                        'landing-menu'  => ['label' => 'Landing Menu', 'tone' => 'tone-blue', 'icon' => 'dripicons-home'],
                        'side-menu'     => ['label' => 'Side Bars', 'tone' => 'tone-purple', 'icon' => 'dripicons-view-list'],
                        'people-menu'   => ['label' => 'People', 'tone' => 'tone-pink', 'icon' => 'dripicons-user'],
                        'settings-menu' => ['label' => 'Admin settings order', 'tone' => 'tone-orange', 'icon' => 'dripicons-gear'],
                        'content-tabs'  => ['label' => 'Content Tabs', 'tone' => 'tone-teal', 'icon' => 'dripicons-toggles'],
                    ];
                    $pageTones = [
                        'home'     => 'tone-green',
                        'about'    => 'tone-teal',
                        'services' => 'tone-gold',
                        'projects' => 'tone-blue',
                        'contact'  => 'tone-pink',
                        'gallery'  => 'tone-red',
                        'menu'     => 'tone-gold',
                        'events'   => 'tone-blue',
                        'register' => 'tone-pink',
                        'footer'   => 'tone-gold',
                    ];
                @endphp
                <p class="text-muted mb-1" style="font-size:12px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;">Pages</p>
                <div class="site-content-tabs-nav">
                    @foreach($schema as $pageKey => $page)
                        @php
                            $tone = $pageTones[$pageKey] ?? 'tone-blue';
                            $icon = $pageKey === 'gallery' ? 'dripicons-photo-group' : 'dripicons-document-edit';
                        @endphp
                        <a class="beyond-module-tab {{ $tone }} {{ $tab == $pageKey ? 'is-active' : '' }}"
                           href="{{ url('/admin/site-content?tab=' . $pageKey) }}">
                            <i class="{{ $icon }}"></i> {{ $page['label'] }}
                        </a>
                    @endforeach
                </div>
                <p class="text-muted mb-1" style="font-size:12px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;">Menus &amp; order</p>
                <div class="site-content-tabs-nav">
                    @foreach($menuTabs as $k => $meta)
                        <a class="beyond-module-tab {{ $meta['tone'] }} {{ $tab == $k ? 'is-active' : '' }}"
                           href="{{ url('/admin/site-content?tab=' . $k) }}">
                            <i class="{{ $meta['icon'] }}"></i> {{ $meta['label'] }}
                        </a>
                    @endforeach
                </div>

                @if(in_array($tab, ['landing-menu', 'side-menu', 'people-menu', 'settings-menu', 'content-tabs'], true))
                    @php
                        if ($tab == 'side-menu') {
                            $items = $side;
                            $order = $sideOrder;
                            $action = route('site-content.side-menu');
                            $heading = 'Side Bars';
                            $hint = 'Show, hide, rename, and reorder admin sidebar items. Site Content and Settings stay visible.';
                        } elseif ($tab == 'people-menu') {
                            $items = $people;
                            $order = $peopleOrder;
                            $action = route('site-content.people-menu');
                            $heading = 'People — Order';
                            $hint = 'Drag items to reorder the People submenu (User List, Customers, Billers, …). Click Save when done.';
                        } elseif ($tab == 'settings-menu') {
                            $items = $settings;
                            $order = $settingsOrder;
                            $action = route('site-content.settings-menu');
                            $heading = 'Settings — Order';
                            $hint = 'Drag items to reorder Settings submenu items (or use arrows). Click Save when done.';
                        } elseif ($tab == 'content-tabs') {
                            $items = \App\Support\SiteContent::contentTabItems();
                            $order = \App\Support\SiteContent::contentTabOrder();
                            $action = route('site-content.content-tabs');
                            $heading = 'Content Tabs — Order';
                            $hint = 'Drag items to reorder Site Content page tabs (or use arrows). Click Save when done.';
                        } else {
                            $items = $landing;
                            $order = $landingOrder;
                            $action = route('site-content.landing-menu');
                            $heading = 'Landing Menu';
                            $hint = 'Show, hide, rename, and reorder the public site header. Login stays in the header separately.';
                        }
                    @endphp
                    <h5 class="mb-1">{{ $heading }}</h5>
                    <p class="text-muted" style="font-size:13px;">{{ $hint }}</p>
                    @if(in_array($tab, ['landing-menu', 'side-menu'], true))
                        <p class="text-info" style="font-size:13px;">Show or hide items, rename the label, and drag to reorder. These changes are website/menu only — they do not change products, members, or other business data.</p>
                    @endif
                    <form method="POST" action="{{ $action }}">
                        @csrf
                        <ul class="list-group reorder-list" id="reorder-list" style="max-width:820px;">
                            @foreach($order as $key)
                                @if(isset($items[$key]))
                                    @php
                                        $canEdit = in_array($tab, ['landing-menu', 'side-menu'], true);
                                        $hiddenList = $tab === 'side-menu' ? ($sideHidden ?? []) : ($landingHidden ?? []);
                                        $labelList = $tab === 'side-menu' ? ($sideLabels ?? []) : ($landingLabels ?? []);
                                        $locked = $tab === 'side-menu' && in_array($key, ['site-content', 'setting'], true);
                                    @endphp
                                    <li class="list-group-item d-flex justify-content-between align-items-center" data-key="{{ $key }}">
                                        <span class="d-flex align-items-center flex-grow-1 mr-2">
                                            <span class="reorder-drag-handle" title="Drag to reorder">⋮⋮</span>
                                            @if($canEdit)
                                                <label class="mb-0 mr-2" title="Show on {{ $tab === 'side-menu' ? 'admin sidebar' : 'public header' }}">
                                                    <input type="checkbox" name="visible[]" value="{{ $key }}" {{ $locked || ! in_array($key, $hiddenList, true) ? 'checked' : '' }} {{ $locked ? 'disabled' : '' }}>
                                                    @if($locked)<input type="hidden" name="visible[]" value="{{ $key }}">@endif
                                                </label>
                                                <input type="text" name="labels[{{ $key }}]" value="{{ $labelList[$key] ?? $items[$key] }}" class="form-control form-control-sm" style="max-width:280px;">
                                            @else
                                                <span>{{ $items[$key] }}</span>
                                            @endif
                                        </span>
                                        <span class="reorder-actions">
                                            <input type="hidden" name="order[]" value="{{ $key }}" class="reorder-order-input">
                                            <button type="button" class="btn btn-sm btn-outline-success move-top" title="Send to top">&#8679;</button>
                                            <button type="button" class="btn btn-sm btn-light move-up" title="Move up one">&#9650;</button>
                                            <button type="button" class="btn btn-sm btn-light move-down" title="Move down one">&#9660;</button>
                                            <button type="button" class="btn btn-sm btn-outline-danger move-bottom" title="Send to bottom">&#8681;</button>
                                        </span>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                        <button type="submit" class="btn btn-primary mt-3">Save</button>
                    </form>

                @elseif($tab == 'gallery' && $pageSchema)
                    @include('site_content.gallery_tab')

                @elseif($pageSchema)
                    @php $tone = $pageTones[$tab] ?? 'tone-blue'; @endphp
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                        <h5 class="mb-0">{{ $pageSchema['label'] }} — Content</h5>
                        <a href="{{ url($pageSchema['url']) }}" target="_blank" rel="noopener" class="beyond-module-tab {{ $tone }} btn-sm" style="padding:8px 14px;">
                            <i class="dripicons-preview"></i> Preview / View live page
                        </a>
                    </div>
                    @if($tab === 'contact')
                        <p class="text-info" style="font-size:13px;"><i class="dripicons-information"></i> Contact details and the message form now appear on the <strong>About Us</strong> page (<code>#contact</code> section). Edit the fields below to update that section.</p>
                    @elseif($tab === 'about')
                        <div class="alert alert-info" style="font-size:13px;">
                            <strong>Photos on About Us</strong>
                            <ul class="mb-0 mt-2 pl-3">
                                <li><strong>Vision photo</strong> and <strong>Mission photo</strong> — choose a file below (or click the dashed box and paste). Then press Save. They appear beside the Vision and Mission text on <a href="{{ url('/about') }}" target="_blank">/about</a>.</li>
                                <li><strong>Leadership photos</strong> — use the sidebar item <a href="{{ url('/admin/leaders') }}">About Us Leaders</a>. Add a leader, upload their photo, and it shows in the Leadership section.</li>
                            </ul>
                        </div>
                    @else
                        <p class="text-muted" style="font-size:13px;">Edit the content shown on this page. Leave a field as-is to keep the current text.</p>
                    @endif

                    <form method="POST" action="{{ route('site-content.content', $tab) }}" enctype="multipart/form-data" style="max-width:820px;">
                        @csrf
                        @foreach($pageSchema['fields'] as $key => $field)
                            @php [$type, $label, $default] = $field; @endphp
                            <div class="form-group">
                                <label class="font-weight-bold">{{ $label }}</label>
                                @if($type == 'image')
                                    @include('components.image_paste', ['name' => 'image[' . $key . ']', 'current' => \App\Support\SiteContent::image($tab . '.' . $key, $default)])
                                @elseif($type == 'textarea' || $type == 'html')
                                    <textarea name="content[{{ $key }}]" rows="{{ $type == 'html' ? 3 : 4 }}" class="form-control">{{ \App\Support\SiteContent::get($tab . '.' . $key, $default) }}</textarea>
                                    @if($type == 'html')<small class="text-muted">HTML is allowed here.</small>@endif
                                @else
                                    <input type="text" name="content[{{ $key }}]" class="form-control" value="{{ \App\Support\SiteContent::get($tab . '.' . $key, $default) }}">
                                @endif
                            </div>
                        @endforeach
                        <button type="submit" class="btn btn-primary mt-2">Save</button>
                        <a href="{{ url($pageSchema['url']) }}" target="_blank" rel="noopener" class="btn btn-light mt-2">Preview</a>
                    </form>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
    var list = document.getElementById('reorder-list');
    if (!list) return;

    function syncOrderInputs() {
        Array.prototype.forEach.call(list.children, function (li) {
            var input = li.querySelector('.reorder-order-input');
            if (input) input.value = li.getAttribute('data-key') || input.value;
        });
    }

    list.addEventListener('click', function (e) {
        var btn = e.target.closest('button');
        if (!btn) return;
        var li = btn.closest('li');
        if (!li) return;
        if (btn.classList.contains('move-top')) {
            if (list.firstElementChild !== li) list.insertBefore(li, list.firstElementChild);
        } else if (btn.classList.contains('move-bottom')) {
            list.appendChild(li);
        } else if (btn.classList.contains('move-up')) {
            var prev = li.previousElementSibling;
            if (prev) list.insertBefore(li, prev);
        } else if (btn.classList.contains('move-down')) {
            var next = li.nextElementSibling;
            if (next) list.insertBefore(next, li);
        }
        syncOrderInputs();
    });

    if (window.Sortable) {
        Sortable.create(list, {
            animation: 150,
            handle: '.reorder-drag-handle, .list-group-item',
            filter: 'button, input, a',
            preventOnFilter: false,
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            onEnd: syncOrderInputs
        });
    }
})();
</script>
@endsection
