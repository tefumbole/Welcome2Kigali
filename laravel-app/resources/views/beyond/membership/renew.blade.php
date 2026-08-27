@extends('beyond.layout')
@section('title', __('site.membership.renew'))
@section('content')
<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-xl mx-auto px-4">
        <div class="bg-white rounded-xl border shadow p-8">
            <h1 class="text-2xl font-extrabold text-brand-blue">{{ __('site.membership.renew') }}</h1>
            <p class="mt-2">{{ optional($membership->customer)->name }} · {{ $membership->number }}</p>
            <p class="text-sm text-gray-600">{{ __('site.membership.status') }}: {{ $membership->status }} · {{ __('site.membership.expires') }}: {{ optional($membership->expires_at)->toFormattedDateString() }}</p>
            @if(session('not_permitted'))
                <div class="mt-3 bg-red-50 text-red-700 p-3 rounded">{{ session('not_permitted') }}</div>
            @endif
            <form method="POST" action="{{ route('membership.renew.pay', $membership->renew_token) }}" class="mt-6 space-y-3">
                @csrf
                @foreach($plans as $plan)
                    <label class="flex items-center justify-between p-3 border rounded-lg cursor-pointer">
                        <span>
                            <input type="radio" name="plan_id" value="{{ $plan->id }}" @if($loop->first) checked @endif>
                            <strong class="ml-2">{{ $plan->name }}</strong>
                            <span class="text-sm text-gray-500">{{ $plan->duration_months }} mo</span>
                        </span>
                        <span class="font-bold">{{ number_format($plan->fee) }} FRW</span>
                    </label>
                @endforeach
                <p class="text-xs text-gray-500">{{ __('site.membership.pay_note') }}</p>
                <button class="w-full bg-brand-blue text-white font-bold py-3 rounded-md">{{ __('site.membership.pay_campay') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
