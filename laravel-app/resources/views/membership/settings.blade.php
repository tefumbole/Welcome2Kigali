@extends('layout.main')
@section('content')
@php $mmTab = 'membership.admin.settings'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('membership.partials.tabs')
        <h1 class="cm-title">Membership settings</h1>
        @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
        <div class="cm-page-card p-4">
            <form method="POST" action="{{ route('membership.admin.settings.update') }}">
                @csrf
                <div class="form-group">
                    <label>Member discount % (Welcome to Kigali Members group)</label>
                    <input type="number" min="0" max="100" step="0.5" name="member_discount_percent" class="form-control" value="{{ $percent }}" style="max-width:160px;">
                    <small class="text-muted">Sales staff cannot change this. POS applies it as a discount, not a markup.</small>
                </div>
                <div class="form-group">
                    <label>Discount policy</label>
                    <select name="discount_policy" class="form-control" style="max-width:280px;">
                        <option value="best" @if($policy==='best') selected @endif>Best Discount Wins (do not stack)</option>
                        <option value="stack" @if($policy==='stack') selected @endif>Stack member % with product promotions</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>ID document types</label>
                    <div>
                        @php $types = json_decode($idTypes, true) ?: []; @endphp
                        <label class="mr-3"><input type="checkbox" name="id_doc_types[]" value="national_id" @if(in_array('national_id', $types)) checked @endif> National ID</label>
                        <label><input type="checkbox" name="id_doc_types[]" value="passport" @if(in_array('passport', $types)) checked @endif> Passport</label>
                    </div>
                </div>
                <h5>WhatsApp reminders</h5>
                @foreach(['remind_30'=>'30 days before','remind_7'=>'7 days before','remind_1'=>'1 day before','remind_on_expiry'=>'On expiry','remind_after_expiry'=>'3 days after expiry'] as $key=>$label)
                    <label class="d-block"><input type="checkbox" name="{{ $key }}" value="1" @if(($remind[$key] ?? '0')==='1') checked @endif> {{ $label }}</label>
                @endforeach
                <button class="btn btn-primary mt-3">Save settings</button>
            </form>
        </div>
    </div>
</section>
@endsection
