<h1>{{ __('mail.booking_details') }}</h1>
<p><strong>{{ __('mail.reference') }}: </strong>{{$reference_no}}</p>
<p>
    <strong>{{ __('mail.booking_status') }}: </strong>
    @if($booking_status==1){{ __('mail.completed') }}
    @elseif($booking_status==2){{ __('mail.pending') }}
    @endif
</p>
<p>
    <strong>{{ __('mail.payment_status') }}: </strong>
    @if($payment_status==1){{ __('mail.pending') }}
    @elseif($payment_status==2){{ __('mail.due') }}
    @elseif($payment_status==3){{ __('mail.partial') }}
    @else{{ __('mail.paid') }}@endif
</p>
<h3>{{ __('mail.order_table') }}</h3>
<table style="border-collapse: collapse; width: 100%;">
    <thead>
    <th style="border: 1px solid #000; padding: 5px">#</th>
    <th style="border: 1px solid #000; padding: 5px">{{ __('mail.product') }}</th>
    <th style="border: 1px solid #000; padding: 5px">{{ __('mail.download_link') }}</th>
    <th style="border: 1px solid #000; padding: 5px">{{ __('mail.qty') }}</th>
    <th style="border: 1px solid #000; padding: 5px">{{ __('mail.duration') }}</th>
    <th style="border: 1px solid #000; padding: 5px">{{ __('mail.unit_price') }}</th>
    <th style="border: 1px solid #000; padding: 5px">{{ __('mail.subtotal') }}</th>
    </thead>
    <tbody>
    @foreach($products as $key=>$product)
        <tr>
            <td style="border: 1px solid #000; padding: 5px">{{$key+1}}</td>
            <td style="border: 1px solid #000; padding: 5px">{{$product}}</td>
            @if($file[$key])
                <td style="border: 1px solid #000; padding: 5px"><a href="{{ $file[$key] }}">{{ __('mail.download') }}</a></td>
            @else
                <td style="border: 1px solid #000; padding: 5px">N/A</td>
            @endif
            <td style="border: 1px solid #000; padding: 5px">{{$qty[$key]}}</td>
            <td style="border: 1px solid #000; padding: 5px">{{$start[$key]}} - {{$end[$key]}}</td>
            <td style="border: 1px solid #000; padding: 5px">{{number_format((float)($total[$key] / $qty[$key]), 2)}}</td>
            <td style="border: 1px solid #000; padding: 5px">{{number_format($total[$key], 2)}}</td>
        </tr>
    @endforeach
    <tr>
        <td colspan="3" style="border: 1px solid #000; padding: 5px"><strong>{{ __('mail.total') }} </strong></td>
        <td style="border: 1px solid #000; padding: 5px">{{$total_qty}}</td>
        <td style="border: 1px solid #000; padding: 5px"></td>
        <td style="border: 1px solid #000; padding: 5px"></td>
        <td style="border: 1px solid #000; padding: 5px">{{$total_price}}</td>
    </tr>
    <tr>
        <td colspan="6" style="border: 1px solid #000; padding: 5px"><strong>{{ __('mail.order_tax') }} </strong> </td>
        <td style="border: 1px solid #000; padding: 5px">{{$order_tax.'('.$order_tax_rate.'%)'}}</td>
    </tr>
    <tr>
        <td colspan="6" style="border: 1px solid #000; padding: 5px"><strong>{{ __('mail.order_discount') }} </strong> </td>
        <td style="border: 1px solid #000; padding: 5px">
            @if($order_discount){{$order_discount}}
            @else 0 @endif
        </td>
    </tr>
    <tr>
        <td colspan="6" style="border: 1px solid #000; padding: 5px"><strong>{{ __('mail.shipping_cost') }}</strong> </td>
        <td style="border: 1px solid #000; padding: 5px">
            @if($shipping_cost){{$shipping_cost}}
            @else 0 @endif
        </td>
    </tr>
    <tr>
        <td colspan="6" style="border: 1px solid #000; padding: 5px"><strong>{{ __('mail.grand_total') }}</strong></td>
        <td style="border: 1px solid #000; padding: 5px">{{number_format($grand_total, 2)}}</td>
    </tr>
    <tr>
        <td colspan="6" style="border: 1px solid #000; padding: 5px"><strong>{{ __('mail.paid_amount') }}</strong></td>
        <td style="border: 1px solid #000; padding: 5px">
            @if($paid_amount){{number_format($paid_amount, 2)}}
            @else 0 @endif
        </td>
    </tr>
    <tr>
        <td colspan="6" style="border: 1px solid #000; padding: 5px"><strong>{{ __('mail.due') }}</strong></td>
        <td style="border: 1px solid #000; padding: 5px">{{number_format((float)($grand_total - $paid_amount), 2)}}</td>
    </tr>
    </tbody>
</table>

<p>{{ __('mail.thank_you') }}</p>
