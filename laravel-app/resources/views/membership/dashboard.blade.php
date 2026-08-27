@extends('layout.main')

@section('content')
@php $mmTab = 'membership.admin.dashboard'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('membership.partials.tabs')
        <h1 class="cm-title"><i class="fa fa-id-card"></i> Membership</h1>
        <p class="cm-subtitle">Welcome to Kigali Expats Club members, applications, and benefits.</p>
        @if(session('message'))
            <div class="alert alert-success">{{ session('message') }}</div>
        @endif
        <div class="row">
            @foreach([
                ['Active', $stats['active'], 'success'],
                ['Expiring (30 days)', $stats['expiring'], 'warning'],
                ['Expired', $stats['expired'], 'danger'],
                ['Pending applications', $stats['pending_apps'], 'info'],
                ['Promotional', $stats['promotional'], 'secondary'],
                ['Paid members', $stats['paid_members'], 'primary'],
                ['Registration revenue', number_format($stats['revenue']).' FRW', 'success'],
                ['Renewal revenue', number_format($stats['renewal_revenue']).' FRW', 'success'],
                ['Free-product value', number_format($stats['free_value']).' FRW', 'warning'],
                ['Promo → paid %', $stats['promo_to_paid'].'%', 'info'],
            ] as $card)
                <div class="col-md-3 mb-3">
                    <div class="cm-page-card p-3">
                        <div class="text-muted small">{{ $card[0] }}</div>
                        <div class="h4 mb-0 text-{{ $card[2] }}">{{ $card[1] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="cm-page-card p-3">
                    <h5>Recent applications</h5>
                    <table class="table table-sm mb-0">
                        @forelse($recent as $app)
                            <tr>
                                <td><a href="{{ route('membership.admin.applications.show', $app->id) }}">{{ $app->reference }}</a></td>
                                <td>{{ $app->full_name }}</td>
                                <td>{{ $app->status }}</td>
                            </tr>
                        @empty
                            <tr><td>No applications yet.</td></tr>
                        @endforelse
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <div class="cm-page-card p-3">
                    <h5>Soonest expiries</h5>
                    <table class="table table-sm mb-0">
                        @forelse($expiring as $m)
                            <tr>
                                <td><a href="{{ route('membership.admin.members.show', $m->id) }}">{{ $m->number }}</a></td>
                                <td>{{ optional($m->customer)->name }}</td>
                                <td>{{ optional($m->expires_at)->toFormattedDateString() }}</td>
                            </tr>
                        @empty
                            <tr><td>None.</td></tr>
                        @endforelse
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
