<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            color: #1f2a44;
            padding: 8pt 10pt 12pt;
        }
        .center { text-align: center; }
        .logo { display: block; height: 42pt; width: auto; margin: 0 auto 2pt; }
        h1 { font-size: 12pt; text-align: center; margin: 0; font-weight: bold; }
        .phone { text-align: center; margin: 1pt 0 0; font-size: 9pt; }
        .doc-title { text-align: center; margin: 1pt 0 6pt; font-size: 11pt; }
        .meta { font-size: 8.5pt; line-height: 1.35; margin: 0 0 6pt; }
        table.items { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.items th, table.items td {
            border: 0.4pt solid #c5ddd0;
            padding: 2pt 2pt;
            vertical-align: top;
            word-wrap: break-word;
        }
        table.items th {
            background: #d9ebe1;
            color: #1f3d32;
            font-size: 7pt;
            text-align: left;
        }
        table.items tr.alt td { background: #f2f8f4; }
        table.items tr.total td { background: #e5f2eb; font-weight: bold; }
        .num { text-align: center; width: 8%; }
        .qty { text-align: center; width: 10%; }
        .money { text-align: right; white-space: nowrap; }
        .c-price { width: 16%; }
        .c-tax { width: 16%; }
        .c-sub { width: 18%; }
        .right { text-align: right; }
        .thanks { text-align: center; color: #5c6578; margin: 6pt 0 4pt; font-size: 8pt; }
        .inv-created-email { display: none; }
        .sign-qr { width: 100%; border-collapse: collapse; margin-top: 2pt; }
        .sign-qr td { border: 0; vertical-align: top; padding: 0; }
        .qr img { width: 68pt; height: 68pt; }
        .bar { text-align: center; margin-top: 4pt; }
        .bar img { width: 180pt; height: 22pt; }
        .inv-admin-sign img, .inv-user-sign img { height: 32pt; width: auto; }
    </style>
</head>
<body>
@php
    $paidAmount = (float) ($lims_sale_data->paid_amount ?? 0);
    $dueAmount = max(0, (float) $lims_sale_data->grand_total - $paidAmount);
    $orderTax = (float) ($lims_sale_data->order_tax ?? 0);
    $orderDiscount = (float) ($lims_sale_data->order_discount ?? 0);
    $couponDiscount = (float) ($lims_sale_data->coupon_discount ?? 0);
    $shippingCost = (float) ($lims_sale_data->shipping_cost ?? 0);
    $generalDiscount = $orderDiscount + $couponDiscount;
    $money = function ($n) {
        $n = (float) $n;
        $decimals = abs($n - round($n)) < 0.005 ? 0 : 2;
        return number_format($n, $decimals);
    };
    $saleStatus = (int) $lims_sale_data->sale_status === 1
        ? trans('file.Completed')
        : ((int) $lims_sale_data->sale_status === 2 ? trans('file.Pending') : trans('file.Draft'));
    $dateText = date(config('date_format') ?: 'd/m/Y', strtotime($lims_sale_data->created_at));
    $qr = \App\Support\SaleInvoiceQr::dataUri($lims_sale_data, true);
    $logoFile = !empty($general_setting->site_logo) ? basename((string) $general_setting->site_logo) : '';
    $logoPath = $logoFile ? public_path('logo/'.$logoFile) : '';
    if (! is_file($logoPath)) {
        $logoPath = public_path('branding/w2k-logo.png');
    }
    $saleNote = trim(strip_tags((string) ($lims_sale_data->sale_note ?? '')));
    $staffNote = trim(strip_tags((string) ($lims_sale_data->staff_note ?? '')));
@endphp
    <div class="center">
        @if(is_file($logoPath))
            <img class="logo" src="{{ $logoPath }}" alt="">
        @endif
    </div>
    <h1>{{ $general_setting->site_title ?? 'Welcome 2 Kigali' }}</h1>
    @if(!empty($lims_biller_data->phone_number))
        <p class="phone">{{ $lims_biller_data->phone_number }}</p>
    @endif
    <p class="doc-title"><strong>Sales Invoice</strong></p>
    <div class="meta">
        <strong>{{ trans('file.reference') }}:</strong> {{ $lims_sale_data->reference_no }}<br>
        <strong>{{ trans('file.Date') }}:</strong> {{ $dateText }}<br>
        <strong>{{ trans('file.Sale Status') }}:</strong> {{ $saleStatus }}<br>
        <strong>{{ trans('file.customer') }}:</strong> {{ optional($lims_customer_data)->name }}<br>
        <strong>{{ trans('file.Phone Number') }}:</strong> {{ optional($lims_customer_data)->phone_number }}<br>
        <strong>{{ trans('file.City') }}:</strong> {{ optional($lims_customer_data)->city }}
    </div>
    <table class="items">
        <tr>
            <th class="num">#</th>
            <th>{{ trans('file.product') }}</th>
            <th class="qty">{{ trans('file.Qty') }}</th>
            <th class="money c-price">{{ trans('file.Unit Price') }}</th>
            <th class="money c-tax">{{ trans('file.Tax') }}</th>
            <th class="money c-sub">{{ trans('file.Subtotal') }}</th>
        </tr>
        @foreach($lims_product_sale_data as $key => $line)
            @php
                $product = \App\Product::find($line->product_id);
                $productName = $product->name ?? 'Item';
                $productCode = $product->code ?? '';
                if ($line->variant_id) {
                    $variant = \App\Variant::find($line->variant_id);
                    if (!empty($variant->name)) {
                        $productName .= ' ['.$variant->name.']';
                    }
                }
                if ($productCode) {
                    $productName .= ' ['.$productCode.']';
                }
                $qty = (float) ($line->qty ?: 0);
                $lineTotal = (float) $line->total;
                $unit = $qty ? ($lineTotal / $qty) : 0;
                $lineTax = (float) ($line->tax ?? 0);
                $taxRate = (float) ($line->tax_rate ?? 0);
            @endphp
            <tr class="{{ $key % 2 === 1 ? 'alt' : '' }}">
                <td class="num"><strong>{{ $key + 1 }}</strong></td>
                <td>{{ $productName }}</td>
                <td class="qty">{{ $qty + 0 }}</td>
                <td class="money">{{ $money($unit) }}</td>
                <td class="money">{{ $money($lineTax) }}({{ $money($taxRate) }}%)</td>
                <td class="money">{{ $money($lineTotal) }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="5"><strong>{{ trans('file.Discount') }}:</strong></td>
            <td class="money">{{ $money($generalDiscount) }}</td>
        </tr>
        <tr class="total">
            <td colspan="4" class="right"><strong>{{ trans('file.Total') }}:</strong></td>
            <td class="money">{{ $money($lims_sale_data->total_tax ?? 0) }}</td>
            <td class="money">{{ $money($lims_sale_data->total_price ?? 0) }}</td>
        </tr>
        @if($orderTax > 0)
            <tr>
                <td colspan="5"><strong>{{ trans('file.Order Tax') }}:</strong></td>
                <td class="money">{{ $money($orderTax) }}({{ $money($lims_sale_data->order_tax_rate ?? 0) }}%)</td>
            </tr>
        @endif
        @if($shippingCost > 0)
            <tr>
                <td colspan="5"><strong>{{ trans('file.Shipping Cost') }}:</strong></td>
                <td class="money">{{ $money($shippingCost) }}</td>
            </tr>
        @endif
        <tr>
            <td colspan="5"><strong>{{ trans('file.grand total') }}:</strong></td>
            <td class="money">{{ $money($lims_sale_data->grand_total) }}</td>
        </tr>
        <tr>
            <td colspan="5"><strong>Amount Paid:</strong></td>
            <td class="money">{{ $money($paidAmount) }}</td>
        </tr>
        <tr>
            <td colspan="5"><strong>Amount Pending:</strong></td>
            <td class="money">{{ $money($dueAmount) }}</td>
        </tr>
    </table>
    @if($saleNote !== '' && strtolower($saleNote) !== 'null')
        <p class="meta"><strong>{{ trans('file.Sale Note') }}:</strong> {{ $saleNote }}</p>
    @endif
    @if($staffNote !== '' && strtolower($staffNote) !== 'null')
        <p class="meta"><strong>{{ trans('file.Staff Note') }}:</strong> {{ $staffNote }}</p>
    @endif
    <p class="thanks">{{ trans('file.Thank you for shopping with us. Please come again') }}</p>
    <table class="sign-qr">
        <tr>
            <td style="width:62%;">
                @include('pdf.partials._created_by_signature', [
                    'createdUser' => $lims_sale_data->user ?? null,
                    'stampDate' => $lims_sale_data->created_at ?? null,
                    'createdClass' => 'created-by',
                ])
            </td>
            <td class="center qr" style="width:38%;">
                <img src="{{ $qr }}" alt="qrcode" width="68" height="68">
            </td>
        </tr>
    </table>
    <div class="bar">
        <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($lims_sale_data->reference_no, 'C128') }}" alt="barcode" width="180" height="22">
    </div>
</body>
</html>
