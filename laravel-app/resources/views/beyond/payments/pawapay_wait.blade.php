@extends('beyond.layout')
@section('title', 'Mobile Money')
@section('content')
<div class="min-h-screen bg-black text-white py-16 px-4"
     x-data="pawapayWait()" x-init="start()">
    <div class="max-w-lg mx-auto border border-brand-gold/40 p-8 text-center">
        <p class="text-[11px] tracking-[0.25em] uppercase text-brand-gold">PawaPay · Mobile Money</p>
        <h1 class="font-serif text-3xl text-brand-gold mt-3">Approve on your phone</h1>
        <p class="mt-4 text-white/70" x-text="message">{{ $deposit->paying_method }} · {{ number_format($deposit->amount) }} {{ $deposit->currency }}</p>
        <p class="mt-2 text-sm text-white/50">{{ $deposit->paying_method }} · {{ number_format($deposit->amount) }} {{ $deposit->currency }}</p>
        <p class="mt-1 text-sm text-white/50">{{ $deposit->phone }}</p>
        <div class="mt-8" x-show="status === 'pending'">
            <div class="inline-block h-10 w-10 border-2 border-brand-gold border-t-transparent rounded-full animate-spin"></div>
        </div>
        <p class="mt-6 text-green-400" x-show="status === 'completed'" x-cloak>Payment received.</p>
        <p class="mt-6 text-red-400" x-show="status === 'failed'" x-cloak>Payment was not completed.</p>
        <a href="{{ $deposit->redirect_url ?: url('/') }}" class="inline-block mt-8 border border-brand-gold text-brand-gold px-6 py-3 text-sm tracking-[0.2em] uppercase">Back</a>
    </div>
</div>
<script>
function pawapayWait() {
    return {
        status: @json($deposit->status),
        message: 'Approve the Mobile Money prompt on your phone.',
        start() {
            this.poll();
            this.timer = setInterval(() => this.poll(), 4000);
        },
        poll() {
            fetch(@json(url('/payments/pawapay/'.$deposit->deposit_id.'/status')), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(r => r.json()).then(data => {
                this.status = data.status;
                if (data.message) this.message = data.message;
                if (data.status === 'completed' || data.status === 'failed') {
                    clearInterval(this.timer);
                    if (data.status === 'completed' && data.redirect) {
                        setTimeout(() => { window.location = data.redirect; }, 1200);
                    }
                }
            }).catch(() => {});
        }
    };
}
</script>
@endsection
