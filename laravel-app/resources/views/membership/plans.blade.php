@extends('layout.main')
@section('content')
@php $mmTab = 'membership.admin.plans'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('membership.partials.tabs')
        <h1 class="cm-title">Plans</h1>
        @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
        <div class="cm-page-card p-3 mb-3">
            <form method="POST" action="{{ route('membership.admin.plans.store') }}" class="form-inline flex-wrap" style="gap:8px;">
                @csrf
                <input name="code" class="form-control" placeholder="code" required>
                <input name="name" class="form-control" placeholder="Name" required>
                <input name="duration_months" type="number" min="1" class="form-control" placeholder="Months" required>
                <input name="fee" type="number" min="0" step="1" class="form-control" placeholder="Fee FRW" required>
                <label class="ml-2"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                <button class="btn btn-primary">Add plan</button>
            </form>
        </div>
        <div class="cm-page-card">
            <table class="table mb-0">
                <thead><tr><th>Code</th><th>Name</th><th>Months</th><th>Fee</th><th>Active</th><th></th></tr></thead>
                <tbody>
                @foreach($items as $plan)
                    <tr>
                        <form method="POST" action="{{ route('membership.admin.plans.update', $plan->id) }}">
                            @csrf
                            <td>{{ $plan->code }}</td>
                            <td><input name="name" class="form-control" value="{{ $plan->name }}"></td>
                            <td><input name="duration_months" type="number" class="form-control" value="{{ $plan->duration_months }}"></td>
                            <td><input name="fee" type="number" class="form-control" value="{{ (int) $plan->fee }}"></td>
                            <td><input type="checkbox" name="is_active" value="1" @if($plan->is_active) checked @endif></td>
                            <td><button class="btn btn-sm btn-primary">Save</button></td>
                        </form>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
