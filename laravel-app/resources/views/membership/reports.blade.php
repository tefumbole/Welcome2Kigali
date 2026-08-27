@extends('layout.main')
@section('content')
@php $mmTab = 'membership.admin.reports'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('membership.partials.tabs')
        <h1 class="cm-title">Reports</h1>
        <div class="row mb-3">
            <div class="col-md-3"><div class="cm-page-card p-3"><div class="small text-muted">Members</div><div class="h4">{{ $stats['total'] }}</div></div></div>
            <div class="col-md-3"><div class="cm-page-card p-3"><div class="small text-muted">Active</div><div class="h4">{{ $stats['active'] }}</div></div></div>
            <div class="col-md-3"><div class="cm-page-card p-3"><div class="small text-muted">Revenue</div><div class="h4">{{ number_format($stats['revenue'] + $stats['renewal_revenue']) }}</div></div></div>
            <div class="col-md-3"><div class="cm-page-card p-3"><div class="small text-muted">Free benefit value</div><div class="h4">{{ number_format($stats['free_value']) }}</div></div></div>
        </div>
        <div class="cm-page-card p-3">
            <h5>Recent benefit redemptions</h5>
            <table class="table table-sm mb-0">
                <thead><tr><th>When</th><th>Member</th><th>Product</th><th>Value</th></tr></thead>
                <tbody>
                @foreach($redemptions as $r)
                    <tr>
                        <td>{{ optional($r->redeemed_at)->toDayDateTimeString() }}</td>
                        <td>{{ optional($r->membership)->number }}</td>
                        <td>#{{ $r->product_id }}</td>
                        <td>{{ number_format($r->value) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
