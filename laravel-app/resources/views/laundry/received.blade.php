@extends('layout.main')

@section('content')
@php
    $tab = 'received';
    $defaultCollect = now()->addDay()->setTime(17, 0)->format('Y-m-d\TH:i');
@endphp
<section class="forms">
    <div class="container-fluid">
        @include('laundry.partials.tabs')
        <div class="laundry-hero">
            <p class="laundry-kicker">Laundry</p>
            <h1>Awaiting Acceptance</h1>
            <p>Dresses from POS. Open a customer, set collection time, then accept. They leave this list and move to Awaiting cleaning.</p>
        </div>
        @include('laundry.partials.flash')
        @include('laundry.partials.customer_list', [
            'orders' => $orders,
            'stage' => 'received',
            'empty' => 'No customers waiting for acceptance.',
            'defaultCollect' => $defaultCollect,
            'namesOnly' => true,
        ])
        {{ $orders->links() }}
    </div>
</section>
@endsection
