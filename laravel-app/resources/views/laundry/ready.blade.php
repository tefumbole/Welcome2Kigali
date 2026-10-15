@extends('layout.main')

@section('content')
@php $tab = 'ready'; @endphp
<section class="forms">
    <div class="container-fluid">
        @include('laundry.partials.tabs')
        <div class="laundry-hero">
            <p class="laundry-kicker">Laundry</p>
            <h1>Ready for collection</h1>
            <p>Open a customer. Take cash or MoMo if unpaid, then Collect. The client must sign to finish.</p>
        </div>
        @include('laundry.partials.flash')
        @include('laundry.partials.customer_list', [
            'orders' => $orders,
            'stage' => 'ready',
            'empty' => 'Nothing waiting for collection.',
        ])
        {{ $orders->links() }}
    </div>
</section>
@endsection
