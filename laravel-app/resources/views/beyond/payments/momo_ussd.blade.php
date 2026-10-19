<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pay with MoMo · Welcome 2 Kigali</title>
    <style>
        body { margin:0; min-height:100vh; background:#0A0A0A; color:#F7F1E8; font-family: Montserrat, system-ui, sans-serif; }
        .box { max-width: 26rem; margin: 0 auto; padding: 3rem 1.25rem; text-align: center; }
        .kicker { letter-spacing:.2em; text-transform:uppercase; color:#C5A059; font-size:11px; margin:0; }
        h1 { font-size:2.2rem; color:#C5A059; margin:.5rem 0 .25rem; }
        .muted { color: rgba(247,241,232,.65); font-size:14px; }
        .qr { background:#fff; display:inline-block; padding:10px; border-radius:12px; margin:1.5rem 0 1rem; }
        .qr img { display:block; width:240px; height:240px; }
        .code { font-family: ui-monospace, Menlo, monospace; font-size:1.25rem; letter-spacing:.06em; color:#fff; background:rgba(197,160,89,.12); border:1px solid rgba(197,160,89,.4); border-radius:10px; padding:.75rem 1rem; margin:0 0 1.25rem; word-break:break-all; }
        .pay { display:block; background:#C5A059; color:#0A0A0A; text-decoration:none; font-weight:800; padding:1rem; border-radius:12px; letter-spacing:.14em; text-transform:uppercase; font-size:14px; }
        .back { display:inline-block; margin-top:1.5rem; color:#C5A059; font-size:12px; letter-spacing:.14em; text-transform:uppercase; text-decoration:none; }
        ol { text-align:left; color:rgba(247,241,232,.75); font-size:13px; line-height:1.6; padding-left:1.25rem; margin:1.5rem 0 0; }
    </style>
</head>
<body>
<div class="box">
    <p class="kicker">MTN MoMo · Welcome 2 Kigali</p>
    <h1>{{ number_format($amount) }} RWF</h1>
    @if($reference)<p class="muted">{{ $reference }}</p>@endif

    <div class="qr"><img src="{{ $qr }}" alt="Scan to dial MoMo"></div>
    <p class="code">{{ $code }}</p>
    <a class="pay" href="{{ $tel }}">Pay {{ number_format($amount) }} RWF</a>

    <ol>
        <li>Scan the QR with your phone camera, or tap Pay on this phone.</li>
        <li>Your phone opens the dialler with the code above. Press call.</li>
        <li>Enter your MoMo PIN to confirm.</li>
    </ol>

    @if($backUrl && \Illuminate\Support\Str::startsWith($backUrl, url('/')))
        <a class="back" href="{{ $backUrl }}">Back</a>
    @endif
</div>
</body>
</html>
