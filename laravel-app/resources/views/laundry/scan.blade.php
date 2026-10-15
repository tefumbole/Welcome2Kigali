<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $order->reference_no }} · {{ $general_setting->site_title ?? 'Laundry' }}</title>
    <style>
        body { margin:0; font-family: Montserrat, system-ui, sans-serif; background:#0A0A0A; color:#F7F1E8; min-height:100vh; }
        .wrap { max-width:640px; margin:0 auto; padding:24px 16px 48px; }
        h1 { color:#C5A059; margin:0 0 8px; font-size:1.4rem; }
        .card { background:#161616; border:1px solid rgba(197,160,89,.35); border-radius:16px; padding:18px; margin-top:16px; }
        .badge { display:inline-block; background:#C5A059; color:#0A0A0A; font-weight:700; padding:.2rem .6rem; border-radius:999px; font-size:.8rem; }
        table { width:100%; border-collapse:collapse; margin-top:8px; }
        th, td { text-align:left; padding:8px 0; border-bottom:1px solid rgba(255,255,255,.08); font-size:14px; }
        a { color:#C5A059; }
        .cd { display:flex; gap:10px; margin:14px 0 4px; }
        .cd-box { flex:1; background:#0A0A0A; border:1px solid rgba(197,160,89,.35); border-radius:12px; text-align:center; padding:10px 6px; }
        .cd-box strong { display:block; font-size:1.4rem; color:#C5A059; }
        .cd-box span { font-size:10px; letter-spacing:.12em; text-transform:uppercase; color:#b9a078; }
        .cd-done { color:#C5A059; font-weight:700; margin:8px 0 0; }
        .item-status { font-size:11px; letter-spacing:.06em; text-transform:uppercase; color:#C5A059; }
    </style>
</head>
<body>
@php
    $targets = [];
    foreach ($order->items as $item) {
        if ($item->collect_at) {
            $targets[] = $item->collect_at;
        }
    }
    if ($order->collect_at) {
        $targets[] = $order->collect_at;
    }
    $soonest = $targets ? collect($targets)->sort()->first() : null;
@endphp
<div class="wrap">
    <h1>{{ $general_setting->site_title ?? 'Welcome 2 Kigali' }}</h1>
    <p>Track laundry <strong>{{ $order->reference_no }}</strong></p>
    <div class="card">
        <p><span class="badge">{{ $order->statusLabel() }}</span></p>
        <p>
            {{ optional($order->customer)->name }}<br>
            Payment: {{ $order->isPaid() ? 'Paid' : 'Due '.number_format($order->dueAmount(), 0) }}
        </p>
        @if($soonest && ! $order->collected_at)
            <div data-countdown data-target="{{ $soonest->toIso8601String() }}">
                <p style="margin:0;color:#b9a078;font-size:12px;letter-spacing:.08em;text-transform:uppercase;">Countdown to collection</p>
                <div class="cd" data-units>
                    <div class="cd-box"><strong class="cd-days">0</strong><span>Days</span></div>
                    <div class="cd-box"><strong class="cd-hours">00</strong><span>Hours</span></div>
                    <div class="cd-box"><strong class="cd-mins">00</strong><span>Mins</span></div>
                    <div class="cd-box"><strong class="cd-secs">00</strong><span>Secs</span></div>
                </div>
                <p class="cd-done" data-done style="display:none;">Ready for collection now</p>
            </div>
            <p>Collect by {{ $soonest->format('d M Y H:i') }}</p>
        @endif
        <table>
            <thead><tr><th>Item</th><th>Qty</th><th>Status</th></tr></thead>
            <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>
                        {{ $item->name }}
                        @if($item->collect_at)
                            <div style="color:#b9a078;font-size:12px;">Due {{ $item->collect_at->format('d M H:i') }}</div>
                        @endif
                    </td>
                    <td>{{ $item->qty + 0 }}</td>
                    <td class="item-status">{{ $item->statusLabel() }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @if($order->notes)
            <p>Note: {{ $order->notes }}</p>
        @endif
        @if($order->collected_at)
            <p>Collected by {{ $order->collector_name }} on {{ $order->collected_at->format('d M Y H:i') }}</p>
        @endif
    </div>
</div>
<script>
(function () {
    function pad(n) { return n < 10 ? '0' + n : String(n); }
    document.querySelectorAll('[data-countdown]').forEach(function (el) {
        var target = new Date(el.getAttribute('data-target')).getTime();
        if (isNaN(target)) return;
        var units = el.querySelector('[data-units]');
        var doneMsg = el.querySelector('[data-done]');
        function tick() {
            var diff = target - Date.now();
            if (diff <= 0) {
                if (units) units.style.display = 'none';
                if (doneMsg) doneMsg.style.display = 'block';
                return false;
            }
            var secs = Math.floor(diff / 1000);
            var days = Math.floor(secs / 86400); secs %= 86400;
            var hours = Math.floor(secs / 3600); secs %= 3600;
            var mins = Math.floor(secs / 60); secs %= 60;
            var d = el.querySelector('.cd-days');
            var h = el.querySelector('.cd-hours');
            var m = el.querySelector('.cd-mins');
            var s = el.querySelector('.cd-secs');
            if (d) d.textContent = days;
            if (h) h.textContent = pad(hours);
            if (m) m.textContent = pad(mins);
            if (s) s.textContent = pad(secs);
            return true;
        }
        if (tick()) setInterval(tick, 1000);
    });
})();
</script>
</body>
</html>
