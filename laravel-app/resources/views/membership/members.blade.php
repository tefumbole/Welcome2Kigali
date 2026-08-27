@extends('layout.main')
@section('content')
@php $mmTab = 'membership.admin.members'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('membership.partials.tabs')
        <h1 class="cm-title">Members</h1>
        <form class="form-inline mb-3" method="GET">
            <input class="form-control mr-2" name="q" value="{{ request('q') }}" placeholder="Number, name, phone">
            <select name="status" class="form-control mr-2">
                <option value="">All</option>
                @foreach(['ACTIVE','EXPIRING','EXPIRED','SUSPENDED','APPROVED_PENDING_PAYMENT','CANCELLED'] as $st)
                    <option value="{{ $st }}" @if(request('status')===$st) selected @endif>{{ $st }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary">Filter</button>
        </form>
        <div class="cm-page-card">
            <table class="table mb-0">
                <thead><tr><th>Number</th><th>Customer</th><th>Plan</th><th>Status</th><th>Expires</th><th></th></tr></thead>
                <tbody>
                @forelse($items as $m)
                    <tr>
                        <td>{{ $m->number }}</td>
                        <td>{{ optional($m->customer)->name }}<br><small>{{ optional($m->customer)->phone_number }}</small></td>
                        <td>{{ optional($m->plan)->name }} @if($m->is_promotional)<span class="badge badge-info">Promo</span>@endif</td>
                        <td>{{ $m->status }}</td>
                        <td>{{ optional($m->expires_at)->toFormattedDateString() }}</td>
                        <td><a class="btn btn-sm btn-primary" href="{{ route('membership.admin.members.show', $m->id) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6">No members yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->appends(request()->query())->links() }}
    </div>
</section>
@endsection
