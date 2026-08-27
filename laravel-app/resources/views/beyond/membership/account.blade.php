@extends('beyond.layout')
@section('title', __('site.membership.account_title'))
@section('content')
<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-4xl mx-auto px-4">
        <h1 class="text-3xl font-extrabold text-brand-blue mb-6">{{ __('site.membership.account_title') }}</h1>
        @if(!$membership)
            <div class="bg-white rounded-xl border p-8">
                <p class="text-gray-600">{{ __('site.membership.no_membership') }}</p>
                <a href="{{ route('membership.apply') }}" class="inline-block mt-4 bg-brand-blue text-white font-bold px-5 py-2.5 rounded-md">{{ __('site.membership.heading') }}</a>
            </div>
        @else
            <div class="bg-white rounded-xl border p-6 mb-4">
                <p class="font-mono text-lg font-bold">{{ $membership->number }}</p>
                <p>{{ __('site.membership.status') }}: <strong>{{ $membership->status }}</strong></p>
                <p>{{ __('site.membership.plan') }}: {{ optional($membership->plan)->name }}</p>
                <p>{{ __('site.membership.expires') }}: {{ optional($membership->expires_at)->toFormattedDateString() }}</p>
                <p>{{ __('site.membership.discount') }}: {{ app(\App\Services\MembershipService::class)->memberDiscountPercent() }}%</p>
                <div class="flex flex-wrap gap-3 mt-4">
                    <a class="bg-brand-gold text-black font-bold px-4 py-2 rounded-md" href="{{ \App\Support\MembershipQr::verifyUrl($membership) }}">{{ __('site.membership.qr') }}</a>
                    @if($membership->confirmation_pdf)
                        <a class="border border-brand-blue text-brand-blue font-bold px-4 py-2 rounded-md" href="{{ route('membership.pdf', $membership) }}">PDF</a>
                    @endif
                    <a class="bg-brand-blue text-white font-bold px-4 py-2 rounded-md" href="{{ \App\Support\MembershipQr::renewUrl($membership) }}">{{ __('site.membership.renew') }}</a>
                </div>
            </div>
            <div class="bg-white rounded-xl border p-6 mb-4">
                <h2 class="font-bold text-brand-blue">{{ __('site.membership.payments') }}</h2>
                @forelse($membership->payments as $p)
                    <p class="text-sm">{{ $p->created_at->toFormattedDateString() }} · {{ number_format($p->amount) }} FRW · {{ $p->status }} · {{ $p->is_renewal ? 'Renewal' : 'New' }}</p>
                @empty
                    <p class="text-sm text-gray-500">—</p>
                @endforelse
            </div>
            <div class="bg-white rounded-xl border p-6">
                <h2 class="font-bold text-brand-blue">{{ __('site.membership.benefits') }}</h2>
                @forelse($membership->redemptions as $r)
                    <p class="text-sm">{{ optional($r->redeemed_at)->toFormattedDateString() }} · product #{{ $r->product_id }} · {{ number_format($r->value) }} FRW</p>
                @empty
                    <p class="text-sm text-gray-500">{{ __('site.membership.no_redemptions') }}</p>
                @endforelse
            </div>
        @endif
    </div>
</div>
@endsection
