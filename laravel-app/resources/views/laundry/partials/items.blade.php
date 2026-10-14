<ul class="mb-0 pl-3">
    @foreach($order->items as $item)
        <li>
            {{ $item->name }} × {{ $item->qty + 0 }}
            @if($item->note)
                <small class="text-muted">— {{ $item->note }}</small>
            @endif
        </li>
    @endforeach
</ul>
@if($order->notes)
    <div class="mt-1"><small><strong>Note:</strong> {{ $order->notes }}</small></div>
@endif
