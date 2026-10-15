@extends('layout.main')

@section('content')
@php $tab = 'cleaning'; @endphp
<section class="forms">
    <div class="container-fluid">
        @include('laundry.partials.tabs')
        <div class="laundry-hero">
            <p class="laundry-kicker">Laundry</p>
            <h1>Awaiting cleaning</h1>
            <p>Select the dresses that are actually clean, then <strong>Mark selected cleaned</strong>. Only those leave this list. Uncleaned dresses stay here.</p>
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
