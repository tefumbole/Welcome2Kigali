@extends('layout.main')

@section('content')
@php $tab = 'collect'; @endphp
<section class="forms">
    <div class="container-fluid">
        @include('laundry.partials.tabs')
        <div class="laundry-hero">
            <p class="laundry-kicker">Laundry</p>
            <h1>Collect</h1>
            <p>The client or nominee signs. After the signature is saved, the process is complete.</p>
        </div>
        @include('laundry.partials.flash')

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
