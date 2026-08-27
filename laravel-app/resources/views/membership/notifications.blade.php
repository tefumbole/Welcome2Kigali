@extends('layout.main')
@section('content')
@php $mmTab = 'membership.admin.notifications'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('membership.partials.tabs')
        <h1 class="cm-title">Notification log</h1>
        <div class="cm-page-card">
            <table class="table mb-0">
                <thead><tr><th>When</th><th>Member</th><th>Kind</th><th>Channel</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($items as $n)
                    <tr>
                        <td>{{ $n->created_at }}</td>
                        <td>{{ optional(optional($n->membership)->customer)->name }} {{ optional($n->membership)->number }}</td>
                        <td>{{ $n->kind }}</td>
                        <td>{{ $n->channel }}</td>
                        <td>{{ $n->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No notifications logged.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>
</section>
@endsection
