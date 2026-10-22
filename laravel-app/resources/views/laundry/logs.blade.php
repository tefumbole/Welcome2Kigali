@extends('layout.main')
@section('content')
@include('laundry.partials.tabs', ['tab' => 'log'])
<section class="container-fluid">
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    <div class="laundry-hero">
        <span class="laundry-kicker">Laundry</span>
        <h1>Order log</h1>
        <p>How long each ticket took from order to collection.</p>
    </div>
    <form method="get" class="form-inline mb-3">
        <input type="date" name="date" class="form-control mr-2" value="{{ request('date') }}">
        <button class="btn btn-primary" type="submit">Filter</button>
    </form>
    <div class="table-responsive" style="background:#fff;border:1px solid #ead9b4;border-radius:16px;">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Customer</th>
                    <th>Placed by</th>
                    <th>Ordered</th>
                    <th>Accepted</th>
                    <th>Cleaned</th>
                    <th>Ready</th>
                    <th>Collected</th>
                    <th>Total time</th>
                </tr>
            </thead>
            <tbody>
            @forelse($orders as $order)
                @php
                    $firstAccept = $order->items->min('accepted_at');
                    $firstClean = $order->items->min('cleaned_at');
                    $readyAt = $order->ready_at;
                    $collectedAt = $order->collected_at;
                    $placed = $order->created_at;
                    $end = $collectedAt ?: $readyAt ?: $firstClean ?: $firstAccept;
                    $total = ($placed && $end) ? $placed->diffInMinutes($end).' min' : '—';
                @endphp
                <tr>
                    <td><a href="{{ route('laundry.show', $order->id) }}">{{ $order->reference_no }}</a></td>
                    <td>{{ optional($order->customer)->name }}</td>
                    <td>{{ optional($order->user)->name ?: '—' }}</td>
                    <td>{{ $placed ? $placed->format('d M H:i') : '—' }}</td>
                    <td>{{ $firstAccept ? \Carbon\Carbon::parse($firstAccept)->format('d M H:i') : '—' }}</td>
                    <td>{{ $firstClean ? \Carbon\Carbon::parse($firstClean)->format('d M H:i') : '—' }}</td>
                    <td>{{ $readyAt ? $readyAt->format('d M H:i') : '—' }}</td>
                    <td>{{ $collectedAt ? $collectedAt->format('d M H:i') : '—' }}</td>
                    <td><strong>{{ $total }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted" style="padding:28px;">No laundry tickets yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
