@extends('layout.main')

@section('content')
@php $tab = 'collect'; @endphp
<section class="forms">
    <div class="container-fluid">
        @include('laundry.partials.tabs')
        <h1 class="mb-3">Laundry — Collect</h1>
        @include('laundry.partials.flash')
        <p class="text-muted">The client (or nominee) must sign. When the signature is saved, the ticket is collected and the process is complete.</p>

        <h5>Awaiting signature</h5>
        <div class="card mb-4">
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Link sent</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($awaiting as $order)
                        <tr>
                            <td><a href="{{ route('laundry.show', $order->id) }}">{{ $order->reference_no }}</a></td>
                            <td>{{ optional($order->customer)->name }}</td>
                            <td>@include('laundry.partials.items')</td>
                            <td>{{ optional($order->collect_sent_at)->format('d M Y H:i') }}</td>
                            <td>
                                <form method="POST" action="{{ route('laundry.collect.send', $order->id) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary">Resend link</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No tickets waiting for a signature.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <h5>Collected</h5>
        <div class="card">
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Collected by</th>
                            <th>Date</th>
                            <th>Signature</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($collected as $order)
                        <tr>
                            <td><a href="{{ route('laundry.show', $order->id) }}">{{ $order->reference_no }}</a></td>
                            <td>{{ optional($order->customer)->name }}</td>
                            <td>{{ $order->collector_name }}</td>
                            <td>{{ optional($order->collected_at)->format('d M Y H:i') }}</td>
                            <td>
                                @if($order->signature_path)
                                    <img src="{{ url('public/'.$order->signature_path) }}" alt="Signature" style="height:40px;background:#fff;">
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No collected tickets yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $collected->links() }}
    </div>
</section>
@endsection
