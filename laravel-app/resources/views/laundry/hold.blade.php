@extends('layout.main')

@section('content')
@php
    $tab = 'hold';
@endphp
<section class="forms">
    <div class="container-fluid">
        @include('laundry.partials.tabs')
        <div class="laundry-hero">
            <p class="laundry-kicker">Laundry</p>
            <h1>On Hold</h1>
            <p>Dresses parked with a comment. Resume to send them back to Awaiting Acceptance, Cleaning, or Ready.</p>
            <p class="mt-2 mb-0"><a href="{{ route('help.index') }}#laundry" class="text-warning">Help · Laundry</a></p>
        </div>
        @include('laundry.partials.flash')
        @include('laundry.partials.customer_list', [
            'orders' => $orders,
            'stage' => 'hold',
            'empty' => 'No dresses on hold.',
        ])
        {{ $orders->links() }}
    </div>
</section>
@endsection
