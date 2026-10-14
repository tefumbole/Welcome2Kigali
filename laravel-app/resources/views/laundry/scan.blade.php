<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $order->reference_no }} · {{ $general_setting->site_title ?? 'Laundry' }}</title>
    <style>
        body { margin:0; font-family: Montserrat, system-ui, sans-serif; background:#0A0A0A; color:#F7F1E8; min-height:100vh; }
        .wrap { max-width:640px; margin:0 auto; padding:24px 16px; }
        h1 { color:#C5A059; margin:0 0 8px; font-size:1.4rem; }
        .card { background:#161616; border:1px solid rgba(197,160,89,.35); border-radius:14px; padding:18px; margin-top:16px; }
        .badge { display:inline-block; background:#C5A059; color:#0A0A0A; font-weight:700; padding:.2rem .6rem; border-radius:999px; font-size:.8rem; }
        table { width:100%; border-collapse:collapse; margin-top:8px; }
        th, td { text-align:left; padding:8px 0; border-bottom:1px solid rgba(255,255,255,.08); }
        a { color:#C5A059; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>{{ $general_setting->site_title ?? 'Welcome 2 Kigali' }}</h1>
    <p>Laundry order <strong>{{ $order->reference_no }}</strong></p>
    <div class="card">
        <p><span class="badge">{{ $order->statusLabel() }}</span></p>
        <p>
            Customer: {{ optional($order->customer)->name }}<br>
            Payment: {{ $order->isPaid() ? 'Paid' : 'Due '.number_format($order->dueAmount(), 0) }}
        </p>
        @if($order->collect_at)
            <p>Collect by: {{ $order->collect_at->format('d M Y H:i') }}</p>
        @elseif($order->cleaning_duration_hours)
            <p>Cleaning time: {{ $order->cleaning_duration_hours }} hours</p>
        @endif
        <table>
            <thead><tr><th>Item</th><th>Qty</th><th>Note</th></tr></thead>
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
        @if($order->notes)
            <p>Note: {{ $order->notes }}</p>
        @endif
        @if($order->collected_at)
            <p>Collected by {{ $order->collector_name }} on {{ $order->collected_at->format('d M Y H:i') }}</p>
        @endif
    </div>
</div>
</body>
</html>
