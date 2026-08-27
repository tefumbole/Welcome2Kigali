@extends('layout.main')
@section('content')
@php $mmTab = 'membership.admin.audit'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('membership.partials.tabs')
        <h1 class="cm-title">Audit trail</h1>
        <div class="cm-page-card">
            <table class="table table-sm mb-0">
                <thead><tr><th>When</th><th>Action</th><th>Membership</th><th>Application</th><th>User</th><th>Meta</th></tr></thead>
                <tbody>
                @forelse($items as $log)
                    <tr>
                        <td>{{ $log->created_at }}</td>
                        <td>{{ $log->action }}</td>
                        <td>{{ $log->membership_id }}</td>
                        <td>{{ $log->application_id }}</td>
                        <td>{{ $log->user_id }}</td>
                        <td><small>{{ $log->meta }}</small></td>
                    </tr>
                @empty
                    <tr><td colspan="6">No audit rows.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>
</section>
@endsection
