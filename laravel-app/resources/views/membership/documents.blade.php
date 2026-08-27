@extends('layout.main')
@section('content')
@php $mmTab = 'membership.admin.documents'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('membership.partials.tabs')
        <h1 class="cm-title">Documents</h1>
        <div class="cm-page-card">
            <table class="table mb-0">
                <thead><tr><th>Application</th><th>Type</th><th>File</th><th>Date</th></tr></thead>
                <tbody>
                @forelse($items as $doc)
                    <tr>
                        <td>{{ optional($doc->application)->reference }}</td>
                        <td>{{ $doc->doc_type }}</td>
                        <td><a href="{{ asset($doc->path) }}" target="_blank">{{ $doc->original_name }}</a></td>
                        <td>{{ $doc->created_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No documents.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>
</section>
@endsection
