@extends('layout.main')

@section('content')
@php $mmTab = 'membership.admin.applications'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('membership.partials.tabs')
        <a href="{{ route('membership.admin.applications') }}">&larr; Applications</a>
        <h1 class="cm-title">{{ $application->reference }}</h1>
        @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
        <div class="row">
            <div class="col-md-7">
                <div class="cm-page-card p-3 mb-3">
                    <p><strong>Name:</strong> {{ $application->full_name }}</p>
                    <p><strong>Email:</strong> {{ $application->email }}</p>
                    <p><strong>Phone:</strong> {{ $application->phone }}</p>
                    <p><strong>Company:</strong> {{ $application->company_name ?: '—' }}</p>
                    <p><strong>ID type:</strong> {{ $application->id_type }}</p>
                    <p><strong>Plan:</strong> {{ optional($application->plan)->name }}</p>
                    <p><strong>Status:</strong> {{ $application->status }}</p>
                    <p><strong>Agreement:</strong> {{ $application->signed_agreement_version }} @ {{ optional($application->signed_at)->toDayDateTimeString() }}</p>
                    @if($application->signature_image)
                        <p><strong>Signature</strong></p>
                        <img src="{{ $application->signature_image }}" alt="Signature" style="max-width:320px;background:#fff;border:1px solid #ddd;">
                    @endif
                    <h5 class="mt-3">Documents</h5>
                    <ul>
                        @foreach($application->documents as $doc)
                            <li><a href="{{ asset($doc->path) }}" target="_blank">{{ $doc->doc_type }} — {{ $doc->original_name }}</a></li>
                        @endforeach
                    </ul>
                    @if($application->membership)
                        <p><strong>Membership:</strong> <a href="{{ route('membership.admin.members.show', $application->membership->id) }}">{{ $application->membership->number }}</a>
                            ({{ $application->membership->status }})</p>
                    @endif
                </div>
            </div>
            <div class="col-md-5">
                <div class="cm-page-card p-3">
                    @if(in_array($application->status, ['PENDING','UNDER_REVIEW']))
                        <form method="POST" action="{{ route('membership.admin.applications.approve', $application->id) }}" class="mb-3">
                            @csrf
                            <button class="btn btn-success btn-block">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('membership.admin.applications.more', $application->id) }}" class="mb-3">
                            @csrf
                            <textarea name="admin_note" class="form-control mb-2" placeholder="What is missing?" required></textarea>
                            <button class="btn btn-warning btn-block">Request more info</button>
                        </form>
                        <form method="POST" action="{{ route('membership.admin.applications.reject', $application->id) }}">
                            @csrf
                            <textarea name="admin_note" class="form-control mb-2" placeholder="Rejection note" required></textarea>
                            <button class="btn btn-danger btn-block" onclick="return confirm('Reject this application?')">Reject</button>
                        </form>
                    @else
                        <p class="text-muted mb-0">This application is {{ $application->status }}.</p>
                    @endif
                    @if($application->admin_note)
                        <hr>
                        <p><strong>Admin note:</strong><br>{{ $application->admin_note }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
