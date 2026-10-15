<ul class="mb-0 pl-3">
    @foreach($order->items as $item)
        <li>
            {{ $item->name }} × {{ $item->qty + 0 }}
            <small class="text-muted">· {{ $item->statusLabel() }}</small>
            @if($item->collect_at)
                <small class="text-muted">· collect {{ $item->collect_at->format('d M H:i') }}</small>
            @endif
            @if($item->cleanedBy)
                <small class="text-muted">· cleaned by {{ $item->cleanedBy->name }}</small>
            @endif
            @if($item->note)
                <small class="text-muted">— {{ $item->note }}</small>
            @endif
        </li>
    @endforeach
</ul>
@if($order->notes)
    <div class="mt-1"><small><strong>Note:</strong> {{ $order->notes }}</small></div>
@endif
