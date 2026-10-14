@extends('layout.main')

@section('content')
@php $tab = 'cleaning'; @endphp
<section class="forms">
    <div class="container-fluid">
        @include('laundry.partials.tabs')
        <h1 class="mb-3">Laundry — Awaiting cleaning</h1>
        @include('laundry.partials.flash')
        <p class="text-muted">When a dress is cleaned, click <strong>Cleaned</strong>. The client is notified and the ticket moves to Ready for collection.</p>
        <div class="card">
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Collect by</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td><a href="{{ route('laundry.show', $order->id) }}">{{ $order->reference_no }}</a></td>
                            <td>{{ optional($order->customer)->name }}</td>
                            <td>@include('laundry.partials.items')</td>
                            <td>
                                {{ optional($order->collect_at)->format('d M Y H:i') ?: '—' }}
                                <div><small>started {{ optional($order->cleaning_started_at)->format('d M H:i') }}</small></div>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('laundry.ready.mark', $order->id) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-success">Cleaned</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Nothing awaiting cleaning.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $orders->links() }}
    </div>
</section>
@endsection
