@extends('layout.main')

@section('content')
@php $mmTab = 'membership.admin.applications'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('membership.partials.tabs')
        <h1 class="cm-title">Applications</h1>
        @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
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
        <div class="cm-page-card">
            <table class="table mb-0">
                <thead><tr><th>Reference</th><th>Name</th><th>Phone</th><th>Plan</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($items as $app)
                    <tr>
                        <td>{{ $app->reference }}</td>
                        <td>{{ $app->full_name }}</td>
                        <td>{{ $app->phone }}</td>
                        <td>{{ optional($app->plan)->name }}</td>
                        <td><span class="badge badge-info">{{ $app->status }}</span></td>
                        <td><a class="btn btn-sm btn-primary" href="{{ route('membership.admin.applications.show', $app->id) }}">Review</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6">No applications.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->appends(request()->query())->links() }}
    </div>
</section>
@endsection
