@extends('layout.main')

@section('content')
@php $tab = $order->status === 'collected' || $order->status === 'awaiting_signature' ? 'collect' : ($order->status === 'ready' ? 'ready' : ($order->status === 'cleaning' ? 'cleaning' : 'received')); @endphp
<section class="forms">
    <div class="container-fluid">
        @include('laundry.partials.tabs')
        <h1 class="mb-3">{{ $order->reference_no }}</h1>
        @include('laundry.partials.flash')
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <p>
                            <strong>Customer:</strong> {{ optional($order->customer)->name }}
                            · {{ optional($order->customer)->phone_number }}<br>
                            <strong>Status:</strong> {{ $order->statusLabel() }}<br>
                            <strong>Sale:</strong> {{ optional($order->sale)->reference_no }}<br>
                            <strong>Payment:</strong>
                            @if($order->isPaid()) Paid
                            @else Due {{ number_format($order->dueAmount(), 0) }}
                            @endif
                        </p>
                        @include('laundry.partials.items')
                        @if($order->collect_at)
                            <p class="mt-2 mb-0">Collect by: {{ $order->collect_at->format('d M Y H:i') }}</p>
                        @elseif($order->cleaning_duration_hours)
                            <p class="mt-2 mb-0">Cleaning time: {{ $order->cleaning_duration_hours }} hours
                                @if($order->cleaning_started_at) (from {{ $order->cleaning_started_at->format('d M Y H:i') }}) @endif
                            </p>
                        @endif
                        @if($order->collector_name)
                            <p class="mt-2 mb-0">Collected by {{ $order->collector_name }} on {{ optional($order->collected_at)->format('d M Y H:i') }}</p>
                        @endif
                        @if($order->signature_path)
                            <div class="mt-2"><img src="{{ url('public/'.$order->signature_path) }}" alt="Signature" style="max-width:280px;background:#fff;border:1px solid #ddd;"></div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <p class="mb-2">Scan for status</p>
                        <img src="{{ $qr }}" alt="QR" style="width:220px;height:220px;">
                        <div class="small text-muted mt-2">{{ $order->scanUrl() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
