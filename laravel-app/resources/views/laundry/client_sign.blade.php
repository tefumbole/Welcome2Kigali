<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Collect laundry {{ $order->reference_no }}</title>
    <style>
        :root { --gold:#C5A059; --ink:#0A0A0A; --cream:#F7F1E8; }
        * { box-sizing:border-box; }
        body { margin:0; font-family: Montserrat, system-ui, sans-serif; background:#0A0A0A; color:var(--cream); min-height:100vh; }
        .wrap { max-width:720px; margin:0 auto; padding:24px 16px 140px; }
        h1 { color:var(--gold); margin:0 0 8px; }
        .card { background:#161616; border:1px solid rgba(197,160,89,.35); border-radius:14px; padding:18px; margin-top:14px; }
        table { width:100%; border-collapse:collapse; }
        th, td { text-align:left; padding:8px 0; border-bottom:1px solid rgba(255,255,255,.08); font-size:14px; }
        input[type=text] { width:100%; padding:12px; border-radius:10px; border:1px solid #444; background:#111; color:#fff; }
        #signature-pad { width:100%; height:220px; border:2px solid var(--gold); border-radius:12px; background:#fff; touch-action:none; }
        .btn { border:0; border-radius:10px; padding:12px 18px; font-weight:700; cursor:pointer; }
        .btn-gold { background:var(--gold); color:var(--ink); }
        .btn-outline { background:transparent; color:var(--cream); border:1px solid #666; }
        .footer-bar { position:fixed; left:0; right:0; bottom:0; background:#111; border-top:1px solid rgba(197,160,89,.3); padding:14px 16px; }
        .footer-inner { max-width:720px; margin:0 auto; display:flex; gap:10px; justify-content:flex-end; }
        .alert { background:#5c1b1b; color:#fff; padding:12px; border-radius:10px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Collect laundry</h1>
    <p>{{ $general_setting->site_title ?? 'Welcome 2 Kigali Expats Club' }} · {{ $order->reference_no }}</p>
    @if(session('not_permitted'))<div class="alert">{{ session('not_permitted') }}</div>@endif
    @if($errors->any())<div class="alert">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

    <div class="card">
        <p>Customer: <strong>{{ optional($order->customer)->name }}</strong></p>
        <table>
            <thead><tr><th>Dress</th><th>Qty</th><th>Note</th></tr></thead>
            <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->qty + 0 }}</td>
                    <td>{{ $item->note }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ url('/laundry-collect/'.$order->collect_token.'/sign') }}" id="sign-form">
        @csrf
        <div class="card">
            <label>Collected by (client or nominee) *</label>
            <input type="text" name="collector_name" required value="{{ old('collector_name', optional($order->customer)->name) }}">
        </div>
        <div class="card">
            <p>Sign below to confirm you have received these garments.</p>
            <canvas id="signature-pad"></canvas>
            <input type="hidden" name="signature_data" id="signature_data">
            <button type="button" class="btn btn-outline" id="clear-pad" style="margin-top:8px;">Clear</button>
        </div>
        <div class="footer-bar">
            <div class="footer-inner">
                <button type="submit" class="btn btn-gold">Confirm collection</button>
            </div>
        </div>
    </form>
</div>
<script>
(function () {
    var canvas = document.getElementById('signature-pad');
    var ctx = canvas.getContext('2d');
    function resize() {
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        var w = canvas.offsetWidth, h = canvas.offsetHeight;
        canvas.width = w * ratio; canvas.height = h * ratio;
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.strokeStyle = '#0A0A0A'; ctx.lineWidth = 2; ctx.lineCap = 'round';
    }
    resize(); window.addEventListener('resize', resize);
    var drawing = false, last = null;
    function pos(e) {
        var r = canvas.getBoundingClientRect();
        var t = e.touches ? e.touches[0] : e;
        return { x: t.clientX - r.left, y: t.clientY - r.top };
    }
    function start(e) { drawing = true; last = pos(e); e.preventDefault(); }
    function move(e) {
        if (!drawing) return;
        var p = pos(e);
        ctx.beginPath(); ctx.moveTo(last.x, last.y); ctx.lineTo(p.x, p.y); ctx.stroke();
        last = p; e.preventDefault();
    }
    function end() { drawing = false; }
    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    window.addEventListener('mouseup', end);
    canvas.addEventListener('touchstart', start, {passive:false});
    canvas.addEventListener('touchmove', move, {passive:false});
    canvas.addEventListener('touchend', end);
    document.getElementById('clear-pad').addEventListener('click', function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    });
    document.getElementById('sign-form').addEventListener('submit', function () {
        document.getElementById('signature_data').value = canvas.toDataURL('image/png');
    });
})();
</script>
</body>
</html>
