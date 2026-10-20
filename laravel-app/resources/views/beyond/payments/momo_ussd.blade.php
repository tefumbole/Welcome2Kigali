<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pay with MoMo · Welcome 2 Kigali</title>
    <style>
        body { margin:0; min-height:100vh; background:#0A0A0A; color:#F7F1E8; font-family: Montserrat, system-ui, sans-serif; }
        .box { max-width: 26rem; margin: 0 auto; padding: 3rem 1.25rem; text-align: center; }
        .kicker { letter-spacing:.2em; text-transform:uppercase; color:#C5A059; font-size:11px; margin:0; }
        h1 { font-size:2.2rem; color:#C5A059; margin:.5rem 0 .25rem; }
        .muted { color: rgba(247,241,232,.65); font-size:14px; }
        .qr { background:#fff; display:inline-block; padding:10px; border-radius:12px; margin:1.5rem 0 1rem; }
        .qr img { display:block; width:240px; height:240px; }
        .pay { display:block; background:#C5A059; color:#0A0A0A; text-decoration:none; font-weight:800; padding:1rem; border-radius:12px; letter-spacing:.14em; text-transform:uppercase; font-size:14px; }
        .paidbtn { display:block; width:100%; margin-top:.9rem; background:transparent; color:#C5A059; border:1px solid rgba(197,160,89,.6); font-weight:800; padding:.9rem 1rem; border-radius:12px; letter-spacing:.12em; text-transform:uppercase; font-size:13px; cursor:pointer; }
        .back { display:inline-block; margin-top:1.5rem; color:#C5A059; font-size:12px; letter-spacing:.14em; text-transform:uppercase; text-decoration:none; }
        ol { text-align:left; color:rgba(247,241,232,.75); font-size:13px; line-height:1.6; padding-left:1.25rem; margin:1.5rem 0 0; }
        .done { margin:1.75rem 0 0; }
        .done .tick { width:72px; height:72px; border-radius:999px; background:rgba(46,160,67,.15); border:2px solid #2ea043; color:#2ea043; display:flex; align-items:center; justify-content:center; font-size:2.4rem; margin:0 auto 1rem; }
        .done h2 { color:#F7F1E8; font-size:1.3rem; margin:0 0 .35rem; }
        .done p { color:rgba(247,241,232,.65); font-size:14px; margin:0; }
    </style>
</head>
<body>
<div class="box">
    <p class="kicker">MTN MoMo · Welcome 2 Kigali</p>
    <h1>{{ number_format($amount) }} RWF</h1>
    @if($reference)<p class="muted">{{ $reference }}</p>@endif

    @if($paid)
        <div class="done">
            <div class="tick">&#10003;</div>
            <h2>Payment complete</h2>
            <p>This payment has been confirmed. This link is no longer active.</p>
        </div>
    @else
        <div class="qr"><img src="{{ $qr }}" alt="Scan to pay with MoMo"></div>
        <a class="pay" href="{{ $tel }}">Pay Now</a>

        <form method="post"
              action="{{ route('momo.ussd.paid', ['purpose' => $purpose, 'id' => $id, 'amount' => $amount, 'token' => $token]) }}{{ $backUrl ? '?back='.urlencode($backUrl) : '' }}"
              onsubmit="return confirm('Confirm that this payment has been completed?');">
            @csrf
            <button type="submit" class="paidbtn">I have paid</button>
        </form>

        <ol>
            <li>Scan the QR with your phone camera, or tap Pay on this phone.</li>
            <li>Your phone opens the dialler. Press call and enter your MoMo PIN.</li>
            <li>After payment, tap &ldquo;I have paid&rdquo; to complete the order.</li>
        </ol>
    @endif

    @if($backUrl && \Illuminate\Support\Str::startsWith($backUrl, url('/')))
        <a class="back" href="{{ $backUrl }}">Back</a>
    @endif
</div>
</body>
</html>
