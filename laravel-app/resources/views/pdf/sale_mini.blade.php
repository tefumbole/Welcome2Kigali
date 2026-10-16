<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 10px; color: #111; }
        .wrap { width: 72mm; padding: 2mm 2mm 4mm; }
        .center { text-align: center; }
        .logo { height: 16mm; width: auto; margin: 0 auto 2mm; }
        h1 { font-size: 12px; text-align: center; margin: 0; }
        .muted { text-align: center; color: #333; margin: 1mm 0 0; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; }
        td { text-align: left; padding: 1px 0; vertical-align: top; }
        .amt { text-align: right; white-space: nowrap; }
        .name { font-weight: bold; }
        .line { border-top: 1px dashed #444; margin: 2mm 0; }
        .qr img { width: 28mm; height: 28mm; }
    </style>
</head>
<body>
@php
    $paidAmount = (float) ($lims_sale_data->paid_amount ?? 0);
    $dueAmount = max(0, (float) $lims_sale_data->grand_total - $paidAmount);
    $qr = \App\Support\SaleInvoiceQr::dataUri($lims_sale_data, true);
    $logoFile = !empty($general_setting->site_logo) ? basename((string) $general_setting->site_logo) : '';
    $logoPath = $logoFile ? public_path('logo/'.$logoFile) : '';
    if (! is_file($logoPath)) {
        $logoPath = public_path('branding/w2k-logo.png');
    }
@endphp
<div class="wrap">
    <div class="center">
        @if(is_file($logoPath))
            <img class="logo" src="{{ $logoPath }}" alt="">
        @endif
        <h1>{{ $general_setting->site_title ?? 'Welcome 2 Kigali' }}</h1>
        @if(!empty($lims_biller_data->company_name))
            <p class="muted">{{ $lims_biller_data->company_name }}</p>
        @endif
        <p class="muted">Sales receipt</p>
    </div>
    <div class="line"></div>
    <div>
        <strong>{{ $lims_sale_data->reference_no }}</strong><br>
        {{ $lims_sale_data->created_at->format('d-m-Y H:i') }}<br>
        {{ optional($lims_customer_data)->name }}
        @if(optional($lims_customer_data)->phone_number)<br>{{ $lims_customer_data->phone_number }}@endif
    </div>
    <div class="line"></div>
    <table>
        @foreach($lims_product_sale_data as $line)
            @php
                $product = \App\Product::find($line->product_id);
                $qty = (float) ($line->qty ?: 0);
                $lineTotal = (float) $line->total;
                $unit = $qty ? ($lineTotal / $qty) : 0;
            @endphp
            <tr>
                <td colspan="2" class="name">{{ $product->name ?? 'Item' }}</td>
            </tr>
            <tr>
                <td>{{ $qty + 0 }} × {{ number_format($unit, 2) }}</td>
                <td class="amt">{{ number_format($lineTotal, 2) }}</td>
            </tr>
        @endforeach
    </table>
    <div class="line"></div>
    <table>
        <tr><td><strong>Total</strong></td><td class="amt"><strong>{{ number_format((float) $lims_sale_data->grand_total, 2) }}</strong></td></tr>
        <tr><td>Paid</td><td class="amt">{{ number_format($paidAmount, 2) }}</td></tr>
        <tr><td>Due</td><td class="amt">{{ number_format($dueAmount, 2) }}</td></tr>
        @foreach($lims_payment_data as $payment)
            <tr><td>{{ $payment->paying_method }}</td><td class="amt">{{ number_format((float) $payment->amount, 2) }}</td></tr>
        @endforeach
    </table>
    <p class="muted" style="margin-top:3mm;">Thank you. Please come again.</p>
    <div class="center qr"><img src="{{ $qr }}" alt="qrcode"></div>
</div>
</body>
</html>
