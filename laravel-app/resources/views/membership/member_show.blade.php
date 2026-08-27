@extends('layout.main')
@section('content')
@php $mmTab = 'membership.admin.members'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('membership.partials.tabs')
        <a href="{{ route('membership.admin.members') }}">&larr; Members</a>
        <h1 class="cm-title">{{ $membership->number }}</h1>
        @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
        <div class="row">
            <div class="col-md-7">
                <div class="cm-page-card p-3 mb-3">
                    <p><strong>Customer:</strong> {{ optional($membership->customer)->name }}</p>
                    <p><strong>Phone:</strong> {{ optional($membership->customer)->phone_number }}</p>
                    <p><strong>Email:</strong> {{ optional($membership->customer)->email }}</p>
                    <p><strong>Status:</strong> {{ $membership->status }}</p>
                    <p><strong>Plan:</strong> {{ optional($membership->plan)->name }}</p>
                    <p><strong>Starts:</strong> {{ optional($membership->starts_at)->toFormattedDateString() }}</p>
                    <p><strong>Expires:</strong> {{ optional($membership->expires_at)->toFormattedDateString() }}</p>
                    <p><strong>QR verify:</strong> <a href="{{ \App\Support\MembershipQr::verifyUrl($membership) }}" target="_blank">Open</a></p>
                    <p><strong>Renew:</strong> <a href="{{ \App\Support\MembershipQr::renewUrl($membership) }}" target="_blank">Link</a></p>
                    @if($membership->confirmation_pdf)
                        <p><a href="{{ asset($membership->confirmation_pdf) }}" target="_blank">Confirmation PDF</a></p>
                    @endif
                    <h5>Payments</h5>
                    <table class="table table-sm">
                        @foreach($membership->payments as $p)
                            <tr><td>{{ $p->reference }}</td><td>{{ number_format($p->amount) }}</td><td>{{ $p->status }}</td><td>{{ $p->is_renewal ? 'Renewal' : 'New' }}</td></tr>
                        @endforeach
                    </table>
                    <h5>Benefit redemptions</h5>
                    <table class="table table-sm">
                        @foreach($membership->redemptions as $r)
                            <tr><td>{{ optional($r->redeemed_at)->toDayDateTimeString() }}</td><td>Product #{{ $r->product_id }}</td><td>{{ number_format($r->value) }}</td></tr>
                        @endforeach
                    </table>
                </div>
            </div>
            <div class="col-md-5">
                <div class="cm-page-card p-3">
                    @if($membership->status === 'SUSPENDED')
                        <form method="POST" action="{{ route('membership.admin.members.unsuspend', $membership->id) }}">@csrf<button class="btn btn-success btn-block">Unsuspend</button></form>
                    @elseif(in_array($membership->status, ['ACTIVE','EXPIRING']))
                        <form method="POST" action="{{ route('membership.admin.members.suspend', $membership->id) }}" class="mb-2">
                            @csrf
                            <textarea name="note" class="form-control mb-2" placeholder="Reason"></textarea>
                            <button class="btn btn-warning btn-block">Suspend</button>
                        </form>
                    @endif
                    @if(!in_array($membership->status, ['CANCELLED']))
                        <form method="POST" action="{{ route('membership.admin.members.cancel', $membership->id) }}" onsubmit="return confirm('Cancel this membership?')">
                            @csrf<button class="btn btn-outline-danger btn-block">Cancel</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
