<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Collected · {{ $order->reference_no }}</title>
    <style>
        body { margin:0; font-family: Montserrat, system-ui, sans-serif; background:#0A0A0A; color:#F7F1E8; min-height:100vh; }
        .wrap { max-width:560px; margin:0 auto; padding:48px 16px; text-align:center; }
        h1 { color:#C5A059; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Thank you</h1>
    <p>Order <strong>{{ $order->reference_no }}</strong> has been handed over to {{ $order->collector_name }}.</p>
    <p>{{ optional($order->collected_at)->format('d M Y H:i') }}</p>
</div>
</body>
</html>
