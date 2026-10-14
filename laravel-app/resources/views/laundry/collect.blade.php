@extends('layout.main')

@section('content')
@php $tab = 'collect'; @endphp
<section class="forms">
    <div class="container-fluid">
        @include('laundry.partials.tabs')
        <h1 class="mb-3">Laundry — Collect</h1>
        @include('laundry.partials.flash')
        <p class="text-muted">Click a customer to see their dresses. The client (or nominee) must sign. When the signature is saved, the ticket is collected.</p>

        <h5>Awaiting signature</h5>
        @include('laundry.partials.customer_list', [
            'orders' => $awaiting,
            'stage' => 'awaiting',
            'empty' => 'No tickets waiting for a signature.',
        ])

        <h5 class="mt-4">Collected</h5>
        @include('laundry.partials.customer_list', [
            'orders' => $collected,
            'stage' => 'collected',
            'empty' => 'No collected tickets yet.',
        ])
        {{ $collected->links() }}
    </div>
</section>
@endsection
