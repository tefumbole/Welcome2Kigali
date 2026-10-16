<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 10px; color: #111; }
        .wrap { padding: 6px 8px 10px; }
        h1 { font-size: 13px; text-align: center; margin: 0 0 2px; }
        .muted { text-align: center; color: #444; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 2px 0; vertical-align: top; }
        .num { text-align: right; }
        .line { border-top: 1px dashed #999; margin: 6px 0; }
        .qr { text-align: center; margin-top: 8px; }
        .qr img { width: 92px; height: 92px; }
    </style>
</head>
<body>
@php
    $paidAmount = (float) ($lims_sale_data->paid_amount ?? 0);
    $dueAmount = max(0, (float) $lims_sale_data->grand_total - $paidAmount);
    $qr = \App\Support\SaleInvoiceQr::dataUri($lims_sale_data, true);
@endphp
<div class="wrap">
    <h1>{{ $general_setting->site_title ?? 'Welcome 2 Kigali' }}</h1>
    <p class="muted">Sales receipt<br>{{ $lims_sale_data->reference_no }} · {{ $lims_sale_data->created_at->format('d-m-Y H:i') }}</p>
    <div><strong>{{ optional($lims_customer_data)->name }}</strong>
        @if(optional($lims_customer_data)->phone_number)<br>{{ $lims_customer_data->phone_number }}@endif
    </div>
    <div class="line"></div>
    <table>
        @foreach($lims_product_sale_data as $line)
            @php $product = \App\Product::find($line->product_id); @endphp
            <tr>
                <td>{{ $product->name ?? 'Item' }} × {{ $line->qty + 0 }}</td>
                <td class="num">{{ number_format((float) $line->total, 2) }}</td>
            </tr>
        @endforeach
    </table>
    <div class="line"></div>
    <table>
        <tr><td>Total</td><td class="num">{{ number_format((float) $lims_sale_data->grand_total, 2) }}</td></tr>
        <tr><td>Paid</td><td class="num">{{ number_format($paidAmount, 2) }}</td></tr>
        <tr><td>Due</td><td class="num">{{ number_format($dueAmount, 2) }}</td></tr>
        @foreach($lims_payment_data as $payment)
            <tr><td>{{ $payment->paying_method }}</td><td class="num">{{ number_format((float) $payment->amount, 2) }}</td></tr>
        @endforeach
    </table>
    <p class="muted" style="margin-top:8px;">Thank you. Please come again.</p>
    <div class="qr"><img src="{{ $qr }}" alt="qrcode"></div>
</div>
</body>
</html>
