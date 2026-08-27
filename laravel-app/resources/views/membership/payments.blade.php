@extends('layout.main')
@section('content')
@php $mmTab = 'membership.admin.payments'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('membership.partials.tabs')
        <h1 class="cm-title">Payments &amp; renewals</h1>
        <div class="cm-page-card">
            <table class="table mb-0">
                <thead><tr><th>Ref</th><th>Member</th><th>Plan</th><th>Amount</th><th>Status</th><th>Type</th><th>Date</th></tr></thead>
                <tbody>
                @forelse($items as $p)
                    <tr>
                        <td>{{ $p->reference }}</td>
                        <td>{{ optional(optional($p->membership)->customer)->name }}<br><small>{{ optional($p->membership)->number }}</small></td>
                        <td>{{ optional($p->plan)->name }}</td>
                        <td>{{ number_format($p->amount) }} FRW</td>
                        <td>{{ $p->status }}</td>
                        <td>{{ $p->is_renewal ? 'Renewal' : 'Registration' }}</td>
                        <td>{{ $p->created_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">No payments.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>
</section>
@endsection
