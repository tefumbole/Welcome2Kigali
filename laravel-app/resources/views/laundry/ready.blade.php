@extends('layout.main')

@section('content')
@php $tab = 'ready'; @endphp
<section class="forms">
    <div class="container-fluid">
        @include('laundry.partials.tabs')
        <h1 class="mb-3">Laundry — Ready for Collection</h1>
        @include('laundry.partials.flash')
        <p class="text-muted">Click a customer to see their dresses. Payment (cash or MoMo) must be taken before Collect.</p>
        @include('laundry.partials.customer_list', [
            'orders' => $orders,
            'stage' => 'ready',
            'empty' => 'Nothing waiting for collection.',
        ])
        {{ $orders->links() }}
    </div>
</section>
@endsection
