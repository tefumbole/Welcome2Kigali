@extends('layout.main')

@section('content')
@php $tab = 'cleaning'; @endphp
<section class="forms">
    <div class="container-fluid">
        @include('laundry.partials.tabs')
        <h1 class="mb-3">Laundry — Awaiting cleaning</h1>
        @include('laundry.partials.flash')
        <p class="text-muted">Click a customer to see their dresses. When a dress is cleaned, click <strong>Cleaned</strong>. The client is notified and the ticket moves to Ready for collection.</p>
        @include('laundry.partials.customer_list', [
            'orders' => $orders,
            'stage' => 'cleaning',
            'empty' => 'Nothing awaiting cleaning.',
        ])
        {{ $orders->links() }}
    </div>
</section>
@endsection
