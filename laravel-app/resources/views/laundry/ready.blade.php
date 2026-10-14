@extends('layout.main')

@section('content')
@php $tab = 'ready'; @endphp
<section class="forms">
    <div class="container-fluid">
        @include('laundry.partials.tabs')
        <h1 class="mb-3">Laundry — Ready for Collection</h1>
        @include('laundry.partials.flash')
        <p class="text-muted">Payment (cash or MoMo) must be taken before Collect. Collect sends a signature link; once signed, the process is complete.</p>
        <div class="card">
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Payment</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td><a href="{{ route('laundry.show', $order->id) }}">{{ $order->reference_no }}</a></td>
                            <td>{{ optional($order->customer)->name }}<br><small>{{ optional($order->customer)->phone_number }}</small></td>
                            <td>@include('laundry.partials.items')</td>
                            <td>
                                @if($order->isPaid())
                                    <span class="badge badge-success">Paid</span>
                                @else
                                    <span class="badge badge-warning d-block mb-1">Due {{ number_format($order->dueAmount(), 0) }}</span>
                                    <form method="POST" action="{{ route('laundry.pay', $order->id) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="amount" value="{{ $order->dueAmount() }}">
                                        <input type="hidden" name="method" value="cash">
                                        <button class="btn btn-sm btn-outline-success">Cash</button>
                                    </form>
                                    <form method="POST" action="{{ route('laundry.pay', $order->id) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="amount" value="{{ $order->dueAmount() }}">
                                        <input type="hidden" name="method" value="momo">
                                        <button class="btn btn-sm btn-outline-warning">MoMo</button>
                                    </form>
                                @endif
                            </td>
                            <td>
                                @if($order->isPaid())
                                    <form method="POST" action="{{ route('laundry.collect.send', $order->id) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-primary">Collect</button>
                                    </form>
                                @else
                                    <button class="btn btn-sm btn-secondary" disabled title="Pay first (cash or MoMo)">Collect</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Nothing waiting for collection.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $orders->links() }}
    </div>
</section>
@endsection
