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
                <div class="pt-2 space-y-3">
                    <label class="text-sm font-semibold">{{ __('site.checkout.payment') }}</label>
                    <div class="flex flex-col gap-2 text-sm">
                        <label><input type="radio" name="pay_with" value="momo" checked> {{ __('site.checkout.momo') }} / {{ __('site.checkout.airtel') }}</label>
                        <label><input type="radio" name="pay_with" value="visa"> {{ __('site.checkout.visa') }}</label>
                    </div>
                    <div class="momo-fields">
                        <div class="mt-2 flex gap-4 text-sm">
                            <label><input type="radio" name="momo_network" value="mtn" checked> MTN MoMo</label>
                            <label><input type="radio" name="momo_network" value="airtel"> Airtel Money</label>
                        </div>
                        <input type="text" name="momo_phone" class="mt-2 w-full border rounded px-3 py-2" placeholder="0793… or 2507…" value="{{ optional($membership->customer)->phone_number }}">
                    </div>
                    @if(strtolower((string) config('services.stripe.mode', 'test')) !== 'live')
                        <p class="text-xs text-gray-500 visa-hint hidden">{{ __('site.checkout.visa_test') }}</p>
                    @endif
                </div>
                <p class="text-xs text-gray-500">{{ __('site.membership.pay_note') }}</p>
                <button class="w-full bg-brand-blue text-white font-bold py-3 rounded-md">{{ __('site.membership.pay_now') }}</button>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    function syncPayWith() {
        var visa = document.querySelector('input[name="pay_with"][value="visa"]');
        var momoFields = document.querySelector('.momo-fields');
        var hint = document.querySelector('.visa-hint');
        var isVisa = visa && visa.checked;
        if (momoFields) momoFields.style.display = isVisa ? 'none' : '';
        if (hint) hint.classList.toggle('hidden', !isVisa);
    }
    document.querySelectorAll('input[name="pay_with"]').forEach(function (el) {
        el.addEventListener('change', syncPayWith);
    });
    syncPayWith();
})();
</script>
@endsection
