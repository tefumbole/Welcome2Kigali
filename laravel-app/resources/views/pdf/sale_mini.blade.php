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
        @if(!empty($lims_biller_data->phone_number))
            <p class="muted">{{ $lims_biller_data->phone_number }}</p>
        @endif
        <p class="muted" style="font-weight:bold;color:#111;">Sales Invoice</p>
    </div>
    <div class="line"></div>
    <div>
        <strong>Reference:</strong> {{ $lims_sale_data->reference_no }}<br>
        <strong>Date:</strong> {{ $lims_sale_data->created_at->format('d-m-Y H:i') }}<br>
        <strong>Sale status:</strong> {{ (int) $lims_sale_data->sale_status === 1 ? 'Completed' : ((int) $lims_sale_data->sale_status === 2 ? 'Pending' : 'Draft') }}<br>
        <strong>Client:</strong> {{ optional($lims_customer_data)->name }}<br>
        <strong>Phone:</strong> {{ optional($lims_customer_data)->phone_number }}<br>
        <strong>City:</strong> {{ optional($lims_customer_data)->city }}
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
        <tr>
            <td>Discount</td>
            <td class="amt">{{ number_format((float) ($lims_sale_data->order_discount ?? 0) + (float) ($lims_sale_data->coupon_discount ?? 0), 2) }}</td>
        </tr>
        <tr><td><strong>Total</strong></td><td class="amt"><strong>{{ number_format((float) $lims_sale_data->grand_total, 2) }}</strong></td></tr>
        <tr><td>Paid</td><td class="amt">{{ number_format($paidAmount, 2) }}</td></tr>
        <tr><td>Due</td><td class="amt">{{ number_format($dueAmount, 2) }}</td></tr>
        @foreach($lims_payment_data as $payment)
            <tr><td>{{ $payment->paying_method }}</td><td class="amt">{{ number_format((float) $payment->amount, 2) }}</td></tr>
        @endforeach
    </table>
    <p class="muted" style="margin-top:3mm;">Thank you. Please come again.</p>
    <table>
        <tr>
            <td style="width:58%;vertical-align:bottom;">
                @include('pdf.partials._created_by_signature', [
                    'createdUser' => $lims_sale_data->user ?? null,
                    'stampDate' => $lims_sale_data->created_at ?? null,
                    'createdClass' => 'created-by',
                ])
            </td>
            <td class="center qr" style="width:42%;vertical-align:bottom;"><img src="{{ $qr }}" alt="qrcode"></td>
        </tr>
    </table>
</div>
</body>
</html>
