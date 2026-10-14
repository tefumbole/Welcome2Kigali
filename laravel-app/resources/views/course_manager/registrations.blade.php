@extends('layout.main')

@section('content')
@php
    $cmTab = 'courses.registrations';
    $canDelete = !empty($canDelete);
@endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('course_manager.partials.tabs')
        <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap" style="gap:12px;">
            <div>
                <h1 class="cm-title">Registration Management</h1>
                <p class="cm-subtitle">Monitor and manage course enrollments / applications.</p>
            </div>
            <div class="d-flex flex-wrap" style="gap:8px;">
                @if($canDelete)
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="reg-select-all">Select all</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="reg-clear-all">Clear</button>
                    <button type="submit" form="regs-delete-form" class="btn btn-danger btn-sm" id="reg-delete-btn" disabled>
                        <i class="dripicons-trash"></i> Delete selected
                    </button>
                @endif
                <a href="{{ route('courses.registrations') }}" class="cm-btn-outline"><i class="dripicons-clockwise"></i> Refresh Data</a>
            </div>
        </div>
        @if(session('message'))
            <div class="alert alert-success">{{ session('message') }}</div>
        @endif
        @if(session('not_permitted'))
            <div class="alert alert-danger">{{ session('not_permitted') }}</div>
        @endif
        <div class="row mb-3">
            <div class="col-6 col-md-3 mb-2"><div class="cm-stat"><p class="label">Total Registrations</p><p class="value">{{ $stats['total'] }}</p></div></div>
            <div class="col-6 col-md-3 mb-2"><div class="cm-stat"><p class="label">Total Revenue</p><p class="value green">${{ number_format($stats['revenue'], 0) }}</p></div></div>
            <div class="col-6 col-md-3 mb-2"><div class="cm-stat"><p class="label">Confirmed</p><p class="value">{{ $stats['confirmed'] }}</p></div></div>
            <div class="col-6 col-md-3 mb-2"><div class="cm-stat"><p class="label">Pending</p><p class="value">{{ $stats['pending'] }}</p></div></div>
        </div>
        <form method="GET" class="form-inline mb-3">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control mr-2 mb-2" placeholder="Search client name, email…">
            <select name="status" class="form-control mr-2 mb-2">
                <option value="all">All Statuses</option>
                @foreach(['pending','confirmed','completed','cancelled'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button class="cm-btn-primary mb-2">Filter</button>
        </form>
        @if($canDelete)
            <form id="regs-delete-form" method="POST" action="{{ route('courses.registrations.destroy-many') }}">
                @csrf
            </form>
        @endif
        <div class="cm-page-card">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            @if($canDelete)
                                <th style="width:36px;"><input type="checkbox" id="reg-check-all" title="Select all"></th>
                            @endif
                            <th>Client Name</th>
                            <th>Contact</th>
                            <th>Date</th>
                            <th>Courses</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $reg)
                            <tr>
                                @if($canDelete)
                                    <td><input type="checkbox" class="reg-row-check" value="{{ $reg->id }}"></td>
                                @endif
                                <td>
                                    <strong>{{ $reg->client_name }}</strong>
                                    <div class="small text-muted">{{ $reg->reference_number }}</div>
                                </td>
                                <td>
                                    <div>{{ $reg->client_email }}</div>
                                    <div class="small text-muted">{{ $reg->client_phone }}</div>
                                </td>
                                <td>{{ optional($reg->created_at)->format('d M Y') }}</td>
                                <td>{{ $reg->course_names ?: '—' }}</td>
                                <td class="cm-price">${{ number_format((float)$reg->total_price, 0) }}</td>
                                <td><span class="cm-badge">{{ $reg->status }}</span></td>
                                <td>
                                    <form method="POST" action="{{ route('courses.registrations.update', $reg->id) }}" class="form-inline">
                                        @csrf
                                        <select name="status" class="form-control form-control-sm mr-1">
                                            @foreach(['pending','confirmed','completed','cancelled'] as $s)
                                                <option value="{{ $s }}" {{ $reg->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                            @endforeach
                                        </select>
                                        <select name="payment_status" class="form-control form-control-sm mr-1">
                                            @foreach(['pending','paid','refunded'] as $s)
                                                <option value="{{ $s }}" {{ $reg->payment_status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                            @endforeach
                                        </select>
                                        <button class="cm-btn-outline">Save</button>
                                        @if($canDelete)
                                            <button type="button" class="btn btn-sm btn-link text-danger reg-delete-one" data-id="{{ $reg->id }}">Delete</button>
                                        @endif
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $canDelete ? 8 : 7 }}" class="text-center text-muted py-4">No registrations found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $items->appends(request()->query())->links() }}</div>
        </div>
    </div>
</section>
@if($canDelete)
<script>
(function () {
    var form = document.getElementById('regs-delete-form');
    var checkAll = document.getElementById('reg-check-all');
    var deleteBtn = document.getElementById('reg-delete-btn');
    if (!form || !deleteBtn) return;
    function rowChecks() { return Array.prototype.slice.call(document.querySelectorAll('.reg-row-check')); }
    function sync() {
        var any = rowChecks().some(function (c) { return c.checked; });
        deleteBtn.disabled = !any;
        if (checkAll) {
            var all = rowChecks();
            checkAll.checked = all.length > 0 && all.every(function (c) { return c.checked; });
            checkAll.indeterminate = any && !checkAll.checked;
        }
    }
    function fillIds(ids) {
        Array.prototype.slice.call(form.querySelectorAll('input[name="ids[]"]')).forEach(function (el) { el.remove(); });
        ids.forEach(function (id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            form.appendChild(input);
        });
    }
    if (checkAll) checkAll.addEventListener('change', function () {
        rowChecks().forEach(function (c) { c.checked = checkAll.checked; });
        sync();
    });
    var selectAll = document.getElementById('reg-select-all');
    var clearAll = document.getElementById('reg-clear-all');
    if (selectAll) selectAll.addEventListener('click', function () { rowChecks().forEach(function (c) { c.checked = true; }); sync(); });
    if (clearAll) clearAll.addEventListener('click', function () { rowChecks().forEach(function (c) { c.checked = false; }); sync(); });
    rowChecks().forEach(function (c) { c.addEventListener('change', sync); });
    form.addEventListener('submit', function (e) {
        var ids = rowChecks().filter(function (c) { return c.checked; }).map(function (c) { return c.value; });
        if (!ids.length) { e.preventDefault(); alert('Select at least one registration to delete.'); return; }
        if (!confirm('Delete ' + ids.length + ' selected registration' + (ids.length === 1 ? '' : 's') + '? This cannot be undone.')) {
            e.preventDefault();
            return;
        }
        fillIds(ids);
    });
    document.querySelectorAll('.reg-delete-one').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-id');
            if (!id || !confirm('Delete this registration? This cannot be undone.')) return;
            fillIds([id]);
            form.submit();
        });
    });
})();
</script>
@endif
@endsection
