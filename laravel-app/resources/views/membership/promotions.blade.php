@extends('layout.main')
@section('content')
@php $mmTab = 'membership.admin.promotions'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('membership.partials.tabs')
        <h1 class="cm-title">Promotions</h1>
        @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
        <div class="cm-page-card p-3 mb-3">
            <form method="POST" action="{{ route('membership.admin.promotions.store') }}">
                @csrf
                <div class="form-row">
                    <div class="col"><input name="name" class="form-control" placeholder="Name" required></div>
                    <div class="col"><input name="free_days" type="number" class="form-control" value="90" placeholder="Free days"></div>
                    <div class="col"><input name="starts_at" type="datetime-local" class="form-control"></div>
                    <div class="col"><input name="ends_at" type="datetime-local" class="form-control"></div>
                    <div class="col-auto"><label><input type="checkbox" name="is_enabled" value="1" checked> Enabled</label></div>
                    <div class="col-auto"><button class="btn btn-primary">Add</button></div>
                </div>
            </form>
        </div>
        @foreach($items as $promo)
            <div class="cm-page-card p-3 mb-2">
                <form method="POST" action="{{ route('membership.admin.promotions.update', $promo->id) }}">
                    @csrf
                    <div class="form-row align-items-end">
                        <div class="col"><label>Name</label><input name="name" class="form-control" value="{{ $promo->name }}"></div>
                        <div class="col"><label>Free days</label><input name="free_days" type="number" class="form-control" value="{{ $promo->free_days }}"></div>
                        <div class="col"><label>Starts</label><input name="starts_at" type="datetime-local" class="form-control" value="{{ optional($promo->starts_at)->format('Y-m-d\TH:i') }}"></div>
                        <div class="col"><label>Ends</label><input name="ends_at" type="datetime-local" class="form-control" value="{{ optional($promo->ends_at)->format('Y-m-d\TH:i') }}"></div>
                        <div class="col-auto"><label><input type="checkbox" name="is_enabled" value="1" @if($promo->is_enabled) checked @endif> Enabled</label></div>
                        <div class="col-auto"><button class="btn btn-primary">Save</button></div>
                    </div>
                    <textarea name="description" class="form-control mt-2" rows="2">{{ $promo->description }}</textarea>
                    @if($promo->isLive())<span class="badge badge-success mt-2">Live</span>@endif
                </form>
            </div>
        @endforeach
    </div>
</section>
@endsection
