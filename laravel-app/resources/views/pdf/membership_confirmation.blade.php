<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Membership {{ $membership->number }}</title>
    @php
        $letterhead_flow = true;
        $general_setting = $general_setting ?? \App\GeneralSetting::first();
    @endphp
    @include('pdf.partials._letter_branded_styles')
    <style>
        .m-block { margin: 12px 0 18px; font-size: 12px; line-height: 1.5; }
        .m-num { font-size: 16px; font-weight: bold; letter-spacing: 1px; }
        .m-meta td { padding: 4px 8px 4px 0; vertical-align: top; }
    </style>
</head>
<body>
@include('pdf.partials._letter_branded_open')
<div class="m-block">
    <p>{{ now()->format('d F Y') }}</p>
    <p>Dear {{ optional($membership->customer)->name }},</p>
    <p>This letter confirms your membership of Welcome 2 Kigali Expats Club.</p>
    <table class="m-meta">
        <tr><td>Membership No.</td><td class="m-num">{{ $membership->number }}</td></tr>
        <tr><td>Status</td><td>{{ $membership->status }}</td></tr>
        <tr><td>Plan</td><td>{{ optional($membership->plan)->name ?: ($membership->is_promotional ? 'Promotional' : '—') }}</td></tr>
        <tr><td>Starts</td><td>{{ optional($membership->starts_at)->format('d F Y') }}</td></tr>
        <tr><td>Expires</td><td>{{ optional($membership->expires_at)->format('d F Y') }}</td></tr>
    </table>
    <p>Present your membership QR at the club for member pricing and benefits while your membership is active.</p>
    <p>Verify: {{ $verifyUrl }}</p>
    <p>Yours faithfully,<br>Welcome 2 Kigali Expats Club</p>
</div>
@include('pdf.partials._letter_branded_close')
</body>
</html>
