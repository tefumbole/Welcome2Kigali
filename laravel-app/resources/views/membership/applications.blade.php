@extends('layout.main')

@section('content')
@php
    $mmTab = 'membership.admin.applications';
    $canDelete = !empty($canDelete);
@endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('membership.partials.tabs')
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:10px;">
            <h1 class="cm-title mb-0">Applications</h1>
            @if($canDelete)
                <div class="d-flex flex-wrap" style="gap:8px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="app-select-all">Select all</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="app-clear-all">Clear</button>
                    <button type="submit" form="apps-delete-form" class="btn btn-danger btn-sm" id="app-delete-btn" disabled>
                        <i class="dripicons-trash"></i> Delete selected
                    </button>
                </div>
            @endif
        </div>
        @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
        @if(session('not_permitted'))<div class="alert alert-danger">{{ session('not_permitted') }}</div>@endif
        <form class="form-inline mb-3" method="GET">
            <input class="form-control mr-2" name="q" value="{{ request('q') }}" placeholder="Search name, phone, reference">
            <select name="status" class="form-control mr-2">
                <option value="">All statuses</option>
                @foreach(['PENDING','UNDER_REVIEW','APPROVED_PENDING_PAYMENT','ACTIVE','REJECTED'] as $st)
                    <option value="{{ $st }}" @if(request('status')===$st) selected @endif>{{ $st }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary">Filter</button>
        </form>
        <form id="apps-delete-form" method="POST" action="{{ route('membership.admin.applications.destroy-many') }}">
            @csrf
            <div class="cm-page-card">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            @if($canDelete)
                                <th style="width:36px;"><input type="checkbox" id="app-check-all" title="Select all"></th>
                            @endif
                            <th>Reference</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($items as $app)
                        <tr>
                            @if($canDelete)
                                <td><input type="checkbox" class="app-row-check" name="ids[]" value="{{ $app->id }}"></td>
                            @endif
                            <td>{{ $app->reference }}</td>
                            <td>{{ $app->full_name }}</td>
                            <td>{{ $app->phone }}</td>
                            <td>{{ optional($app->plan)->name }}</td>
                            <td><span class="badge badge-info">{{ $app->status }}</span></td>
                            <td style="white-space:nowrap;">
                                <a class="btn btn-sm btn-primary" href="{{ route('membership.admin.applications.show', $app->id) }}">Review</a>
                                @if($canDelete)
                                    <button type="button" class="btn btn-sm btn-link text-danger app-delete-one" data-id="{{ $app->id }}">Delete</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canDelete ? 7 : 6 }}">No applications.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </form>
        {{ $items->appends(request()->query())->links() }}
    </div>
</section>
@if($canDelete)
<script>
(function () {
    var form = document.getElementById('apps-delete-form');
    var checkAll = document.getElementById('app-check-all');
    var deleteBtn = document.getElementById('app-delete-btn');
    if (!form || !deleteBtn) return;
    function rowChecks() { return Array.prototype.slice.call(document.querySelectorAll('.app-row-check')); }
    function sync() {
        var any = rowChecks().some(function (c) { return c.checked; });
        deleteBtn.disabled = !any;
        if (checkAll) {
            var all = rowChecks();
            checkAll.checked = all.length > 0 && all.every(function (c) { return c.checked; });
            checkAll.indeterminate = any && !checkAll.checked;
        }
    }
    if (checkAll) checkAll.addEventListener('change', function () {
        rowChecks().forEach(function (c) { c.checked = checkAll.checked; });
        sync();
    });
    var selectAll = document.getElementById('app-select-all');
    var clearAll = document.getElementById('app-clear-all');
    if (selectAll) selectAll.addEventListener('click', function () { rowChecks().forEach(function (c) { c.checked = true; }); sync(); });
    if (clearAll) clearAll.addEventListener('click', function () { rowChecks().forEach(function (c) { c.checked = false; }); sync(); });
    rowChecks().forEach(function (c) { c.addEventListener('change', sync); });
    form.addEventListener('submit', function (e) {
        var n = rowChecks().filter(function (c) { return c.checked; }).length;
        if (!n) { e.preventDefault(); alert('Select at least one application to delete.'); return; }
        if (!confirm('Delete ' + n + ' selected application' + (n === 1 ? '' : 's') + '? This cannot be undone.')) e.preventDefault();
    });
    document.querySelectorAll('.app-delete-one').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('Delete this application? This cannot be undone.')) return;
            rowChecks().forEach(function (c) { c.checked = false; });
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = btn.getAttribute('data-id');
            form.appendChild(input);
            form.submit();
        });
    });
})();
</script>
@endif
@endsection
