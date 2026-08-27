@extends('layout.main')
@section('content')
@php $mmTab = 'membership.admin.agreements'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('membership.partials.tabs')
        <h1 class="cm-title">Agreements</h1>
        @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
        <div class="cm-page-card p-3 mb-3">
            <form method="POST" action="{{ route('membership.admin.agreements.store') }}">
                @csrf
                <div class="form-row">
                    <div class="col-md-2"><input name="version" class="form-control" placeholder="Version" required></div>
                    <div class="col-md-8"><input name="title" class="form-control" placeholder="Title" required></div>
                    <div class="col-md-2"><label class="mt-2"><input type="checkbox" name="is_current" value="1"> Current</label></div>
                </div>
                <textarea name="body" class="form-control mt-2" rows="8" required></textarea>
                <button class="btn btn-primary mt-2">Save version</button>
            </form>
        </div>
        @foreach($items as $ag)
            <div class="cm-page-card p-3 mb-2">
                <strong>{{ $ag->title }}</strong> v{{ $ag->version }}
                @if($ag->is_current)<span class="badge badge-success">Current</span>@endif
                <pre class="mt-2 mb-0" style="white-space:pre-wrap;">{{ $ag->body }}</pre>
            </div>
        @endforeach
    </div>
</section>
@endsection
