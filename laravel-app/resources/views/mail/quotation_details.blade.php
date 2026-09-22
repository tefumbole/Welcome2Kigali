<img src="{{url('public/logo', $header)}}" style=" width: 100%;">
<h1>{{ __('mail.quotation_details') }}</h1>
<p><strong>{{ __('mail.reference') }}: </strong>{{$reference_no}}</p>
<h3>{{ __('mail.order_table') }}</h3>

<table style="border-collapse: collapse; width: 100%; background: url({{url('public/logo', $water_mark)}}); background-repeat: no-repeat; background-position: center; background-size: contain;">
	<thead>
		<th style="border: 1px solid #000; padding: 5px">#</th>
		<th style="border: 1px solid #000; padding: 5px">{{ __('mail.product') }}</th>
		<th style="border: 1px solid #000; padding: 5px">{{ __('mail.qty') }}</th>
		<th style="border: 1px solid #000; padding: 5px">{{ __('mail.unit_price') }}</th>
		<th style="border: 1px solid #000; padding: 5px">{{ __('mail.subtotal') }}</th>
	</thead>
	<tbody>
		@foreach($products as $key=>$product)
		<tr>
			<td style="border: 1px solid #000; padding: 5px">{{$key+1}}</td>
			<td style="border: 1px solid #000; padding: 5px">{{$product}}</td>
			<td style="border: 1px solid #000; padding: 5px">{{$qty[$key].' '.$unit[$key]}}</td>
			<td style="border: 1px solid #000; padding: 5px">{{number_format($total[$key] / $qty[$key])}}</td>
			<td style="border: 1px solid #000; padding: 5px">{{number_format($total[$key])}}</td>
		</tr>
		@endforeach
		<tr>
			<td colspan="2" style="border: 1px solid #000; padding: 5px"><strong>{{ __('mail.total') }} </strong></td>
			<td style="border: 1px solid #000; padding: 5px">{{$total_qty}}</td>
			<td style="border: 1px solid #000; padding: 5px"></td>
			<td style="border: 1px solid #000; padding: 5px">{{number_format($total_price)}}</td>
		</tr>
		<tr>
			<td colspan="4" style="border: 1px solid #000; padding: 5px"><strong>{{ __('mail.order_tax') }} </strong> </td>
			<td style="border: 1px solid #000; padding: 5px">{{$order_tax.'('.$order_tax_rate.'%)'}}</td>
		</tr>
		<tr>
			<td colspan="4" style="border: 1px solid #000; padding: 5px"><strong>{{ __('mail.order_discount') }} </strong> </td>
			<td style="border: 1px solid #000; padding: 5px">
				@if($order_discount){{$order_discount}}
				@else 0 @endif
			</td>
		</tr>
		<tr>
			<td colspan="4" style="border: 1px solid #000; padding: 5px"><strong>{{ __('mail.shipping_cost') }}</strong> </td>
			<td style="border: 1px solid #000; padding: 5px">
				@if($shipping_cost){{$shipping_cost}}
				@else 0 @endif
			</td>
		</tr>
		<tr>
			<td colspan="4" style="border: 1px solid #000; padding: 5px"><strong>{{ __('mail.grand_total') }}</strong></td>
			<td style="border: 1px solid #000; padding: 5px">{{number_format($grand_total)}}</td>
		</tr>
	</tbody>
</table>

<p>{{ __('mail.thank_you') }}</p>
<img src="{{url('public/logo', $footer)}}" style=" width: 100%;">
