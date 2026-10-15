<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mobile Money</title>
    <style>
        body { margin:0; min-height:100vh; background:#0A0A0A; color:#F7F1E8; font-family: Montserrat, system-ui, sans-serif; }
        .box { max-width: 28rem; margin: 0 auto; padding: 4rem 1.25rem; text-align: center; }
        .kicker { letter-spacing:.2em; text-transform:uppercase; color:#C5A059; font-size:11px; }
        h1 { font-size:1.8rem; color:#C5A059; margin:.75rem 0 1rem; }
        .muted { color: rgba(247,241,232,.65); }
        .spin { width:40px; height:40px; margin:2rem auto 0; border:2px solid #C5A059; border-top-color:transparent; border-radius:50%; animation:spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .ok { color:#86efac; }
        .bad { color:#fca5a5; }
        a { display:inline-block; margin-top:2rem; border:1px solid #C5A059; color:#C5A059; text-decoration:none; padding:.75rem 1.5rem; letter-spacing:.16em; text-transform:uppercase; font-size:12px; }
        [x-cloak] { display:none !important; }
    </style>
</head>
<body>
<div class="box" x-data="pawapayWait()" x-init="start()">
    <p class="kicker">PawaPay · Mobile Money</p>
    <h1>Approve on your phone</h1>
    <p class="muted" x-text="message">Approve the Mobile Money prompt on your phone.</p>
    <p class="muted">{{ $deposit->paying_method }} · {{ number_format($deposit->amount) }} {{ $deposit->currency }}</p>
    <p class="muted">{{ $deposit->phone }}</p>
    <div class="spin" x-show="status === 'pending'"></div>
    <p class="ok" x-show="status === 'completed'" x-cloak>Payment received.</p>
    <p class="bad" x-show="status === 'failed'" x-cloak>Payment was not completed.</p>
    <a href="{{ $deposit->redirect_url ?: url('/') }}">Back</a>
</div>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
</body>
</html>
