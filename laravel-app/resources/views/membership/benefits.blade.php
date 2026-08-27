@extends('layout.main')
@section('content')
@php $mmTab = 'membership.admin.benefits'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('membership.partials.tabs')
        <h1 class="cm-title">Member product benefits</h1>
        @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
        <div class="cm-page-card p-3 mb-3">
            <form method="POST" action="{{ route('membership.admin.benefits.store') }}" class="form-inline flex-wrap" style="gap:8px;">
                @csrf
                <select name="product_id" class="form-control" required>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->code }})</option>
                    @endforeach
                </select>
                <select name="kind" class="form-control">
                    <option value="free">Free</option>
                    <option value="member_price">Member price</option>
                </select>
                <input name="member_price" class="form-control" placeholder="Member price">
                <input name="qty" type="number" value="1" class="form-control" style="width:80px;">
                <select name="frequency" class="form-control">
                    <option value="unlimited">Unlimited</option>
                    <option value="once_per_day">Per day</option>
                    <option value="once_per_week">Per week</option>
                    <option value="once_per_month">Per month</option>
                    <option value="once_per_period">Per period</option>
                </select>
                <button class="btn btn-primary">Save benefit</button>
            </form>
        </div>
        <div class="cm-page-card">
            <table class="table mb-0">
                <thead><tr><th>Product</th><th>Kind</th><th>Price</th><th>Qty</th><th>Frequency</th><th>Active</th></tr></thead>
                <tbody>
                @foreach($items as $b)
                    <tr>
                        <td>{{ optional($b->product)->name }}</td>
                        <td>{{ $b->kind }}</td>
                        <td>{{ $b->member_price }}</td>
                        <td>{{ $b->qty }}</td>
                        <td>{{ $b->frequency }}</td>
                        <td>{{ $b->is_active ? 'Yes' : 'No' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
