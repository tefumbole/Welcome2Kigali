@extends('layout.main')

@section('content')
@php $tab = 'cleaning'; @endphp
<section class="forms">
    <div class="container-fluid">
        @include('laundry.partials.tabs')
        <div class="laundry-hero">
            <p class="laundry-kicker">Laundry</p>
            <h1>Awaiting cleaning</h1>
            <p>Open a customer. When a dress is done, click <strong>Cleaned</strong>. The client is notified and it moves to Ready for collection.</p>
        </div>
        @include('laundry.partials.flash')
        @include('laundry.partials.customer_list', [
            'orders' => $orders,
            'stage' => 'cleaning',
            'empty' => 'Nothing awaiting cleaning.',
        ])
        {{ $orders->links() }}
    </div>
</section>
@endsection
