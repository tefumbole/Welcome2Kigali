<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Link expired</title>
    <style>
        body { margin:0; font-family: Montserrat, system-ui, sans-serif; background:#0A0A0A; color:#F7F1E8; min-height:100vh; }
        .wrap { max-width:560px; margin:0 auto; padding:48px 16px; text-align:center; }
        h1 { color:#C5A059; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>This link has expired</h1>
    <p>{{ optional($general_setting)->site_title ?? 'Welcome 2 Kigali Expats Club' }}</p>
    <p>Ask the club to send a new collection link.</p>
</div>
</body>
</html>
