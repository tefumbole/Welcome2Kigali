@extends('layout.main')

@section('content')
@php $tab = 'cleaning'; @endphp
<section class="forms">
    <div class="container-fluid">
        @include('laundry.partials.tabs')
        <h1 class="mb-3">Laundry — Cleaning</h1>
        @include('laundry.partials.flash')
        <div class="card">
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Duration</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td><a href="{{ route('laundry.show', $order->id) }}">{{ $order->reference_no }}</a></td>
                            <td>{{ optional($order->customer)->name }}</td>
                            <td>@include('laundry.partials.items')</td>
                            <td><span class="badge badge-info">{{ $order->statusLabel() }}</span></td>
                            <td>
                                @if($order->status === 'cleaning')
                                    {{ $order->cleaning_duration_hours }} h
                                    <div><small>since {{ optional($order->cleaning_started_at)->format('d M H:i') }}</small></div>
                                @else
                                    —
                                @endif
                            </td>
                            <td style="white-space:nowrap;">
                                @if($order->status === 'received')
                                    <form method="POST" action="{{ route('laundry.cleaning.start', $order->id) }}" class="form-inline">
                                        @csrf
                                        <input type="number" name="hours" min="1" value="24" class="form-control form-control-sm mr-1" style="width:70px;" title="Hours">
                                        <button class="btn btn-sm btn-primary">Accept</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('laundry.ready.mark', $order->id) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-success">Mark completed</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No tickets in cleaning.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $orders->links() }}
    </div>
</section>
@endsection
