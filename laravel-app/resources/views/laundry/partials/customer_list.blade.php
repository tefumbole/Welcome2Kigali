@php
    $stage = $stage ?? 'received';
    $empty = $empty ?? 'Nothing here.';
    $defaultCollect = $defaultCollect ?? now()->addDay()->setTime(17, 0)->format('Y-m-d\TH:i');
    $groups = [];
    foreach ($orders as $order) {
        $key = $order->customer_id ? (string) $order->customer_id : 'order-'.$order->id;
        if (! isset($groups[$key])) {
            $groups[$key] = [
                'customer' => $order->customer,
                'orders' => [],
                'dresses' => 0,
                'due' => 0,
                'unpaid' => false,
                'collect_at' => null,
            ];
        }
        $groups[$key]['orders'][] = $order;
        foreach ($order->items as $item) {
            $groups[$key]['dresses'] += (float) $item->qty;
            if ($item->collect_at && ($groups[$key]['collect_at'] === null || $item->collect_at->lt($groups[$key]['collect_at']))) {
                $groups[$key]['collect_at'] = $item->collect_at;
            }
        }
        if ($order->collect_at && ($groups[$key]['collect_at'] === null || $order->collect_at->lt($groups[$key]['collect_at']))) {
            $groups[$key]['collect_at'] = $order->collect_at;
        }
        if (! $order->isPaid()) {
            $groups[$key]['unpaid'] = true;
            $groups[$key]['due'] += $order->dueAmount();
        }
    }
    $accordionId = 'laundry-acc-'.$stage;
    $namesOnly = ! empty($namesOnly);
@endphp

@if(count($groups))
<div class="laundry-customer-list mb-3" id="{{ $accordionId }}">
    @foreach($groups as $key => $group)
        @php
            $customer = $group['customer'];
            $dressCount = $group['dresses'];
            $dressLabel = (floor($dressCount) == $dressCount) ? (int) $dressCount : $dressCount;
            $panelId = $accordionId.'-'.$key;
            $ticketCount = count($group['orders']);
        @endphp
        <div class="laundry-customer-row">
            <button type="button" class="laundry-customer-btn" data-toggle="collapse" data-target="#{{ $panelId }}" aria-expanded="false" aria-controls="{{ $panelId }}">
                <span class="laundry-customer-name">{{ optional($customer)->name ?: 'Unknown customer' }}</span>
                @unless($namesOnly)
                    <span class="laundry-customer-meta">
                        @if(optional($customer)->phone_number){{ $customer->phone_number }} · @endif
                        {{ $dressLabel }} {{ $dressLabel == 1 ? 'dress' : 'dresses' }}
                        @if($ticketCount > 1) · {{ $ticketCount }} tickets @endif
                        @if($group['collect_at']) · collect {{ $group['collect_at']->format('d M H:i') }} @endif
                    </span>
                    @if(in_array($stage, ['received', 'ready'], true))
                        @if($group['unpaid'])
                            <span class="badge badge-warning">Due {{ number_format($group['due'], 0) }}</span>
                        @else
                            <span class="badge badge-success">Paid</span>
                        @endif
                    @endif
                @endunless
                <i class="fa fa-chevron-down chev"></i>
            </button>
            <div id="{{ $panelId }}" class="collapse" data-parent="#{{ $accordionId }}">
                <div class="laundry-customer-body">
                    @foreach($group['orders'] as $order)
                        <div class="laundry-order-block">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <a href="{{ route('laundry.show', $order->id) }}"><strong>{{ $order->reference_no }}</strong></a>
                                @if($stage === 'collected')
                                    <span class="text-muted small">{{ $order->collector_name }} · {{ optional($order->collected_at)->format('d M Y H:i') }}</span>
                                @elseif($stage === 'awaiting')
                                    <span class="text-muted small">Link sent {{ optional($order->collect_sent_at)->format('d M Y H:i') ?: '—' }}</span>
                                @elseif($stage === 'cleaning')
                                    <span class="text-muted small">Started {{ optional($order->cleaning_started_at)->format('d M H:i') ?: '—' }}</span>
                                @endif
                            </div>

                            @if($stage === 'received')
                                <form method="POST" action="{{ route('laundry.accept', $order->id) }}">
                                    @csrf
                            @endif

                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        @if($stage === 'received')<th style="width:40px;">Select</th>@endif
                                        <th>Dress</th>
                                        <th>Qty</th>
                                        <th>Price</th>
                                        <th>Note</th>
                                        <th>Collect by</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($order->items as $item)
                                    @php
                                        $collectValue = optional($item->collect_at)->format('Y-m-d\TH:i') ?: $defaultCollect;
                                    @endphp
                                    <tr>
                                        @if($stage === 'received')
                                            <td><input type="checkbox" name="item_id[]" value="{{ $item->id }}" checked></td>
                                        @endif
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->qty + 0 }}</td>
                                        <td>{{ number_format((float) $item->total, 0) }}</td>
                                        <td>{{ $item->note ?: '—' }}</td>
                                        <td>
                                            @if($stage === 'received')
                                                <input type="datetime-local" name="item_collect_at[{{ $item->id }}]" class="form-control form-control-sm item-collect" value="{{ $collectValue }}">
                                            @else
                                                {{ optional($item->collect_at ?: $order->collect_at)->format('d M Y H:i') ?: '—' }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                            @if($order->notes)
                                <div class="laundry-comment"><strong>Comment:</strong> {{ $order->notes }}</div>
                            @endif

                            @if($stage === 'received')
                                    <div class="form-row align-items-end">
                                        <div class="form-group col-md-5 mb-2">
                                            <label class="small mb-1">Collection date &amp; time for all selected</label>
                                            <input type="datetime-local" name="collect_at" class="form-control form-control-sm collect-all" value="{{ optional($order->collect_at)->format('Y-m-d\TH:i') ?: $defaultCollect }}" required>
                                        </div>
                                        <div class="form-group col-md-3 mb-2">
                                            @if($order->isPaid())
                                                <span class="badge badge-success">Paid</span>
                                            @else
                                                <span class="badge badge-warning">Due {{ number_format($order->dueAmount(), 0) }}</span>
                                            @endif
                                        </div>
                                        <div class="form-group col-md-4 mb-2 text-md-right">
                                            <button type="submit" class="btn btn-primary">Accept &amp; send to cleaning</button>
                                        </div>
                                    </div>
                                </form>
                            @elseif($stage === 'cleaning')
                                <form method="POST" action="{{ route('laundry.ready.mark', $order->id) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-success">Cleaned</button>
                                </form>
                            @elseif($stage === 'ready')
                                <div class="d-flex flex-wrap align-items-center" style="gap:8px;">
                                    @if($order->isPaid())
                                        <span class="badge badge-success">Paid</span>
                                        <form method="POST" action="{{ route('laundry.collect.send', $order->id) }}" class="mb-0">
                                            @csrf
                                            <button class="btn btn-sm btn-primary">Collect</button>
                                        </form>
                                    @else
                                        <span class="badge badge-warning">Due {{ number_format($order->dueAmount(), 0) }}</span>
                                        <form method="POST" action="{{ route('laundry.pay', $order->id) }}" class="mb-0">
                                            @csrf
                                            <input type="hidden" name="amount" value="{{ $order->dueAmount() }}">
                                            <input type="hidden" name="method" value="cash">
                                            <button class="btn btn-sm btn-outline-success">Cash</button>
                                        </form>
                                        <form method="POST" action="{{ route('laundry.pay', $order->id) }}" class="mb-0">
                                            @csrf
                                            <input type="hidden" name="amount" value="{{ $order->dueAmount() }}">
                                            <input type="hidden" name="method" value="momo">
                                            <button class="btn btn-sm btn-outline-warning">MoMo</button>
                                        </form>
                                        <button class="btn btn-sm btn-secondary" disabled title="Pay first (cash or MoMo)">Collect</button>
                                    @endif
                                </div>
                            @elseif($stage === 'awaiting')
                                <form method="POST" action="{{ route('laundry.collect.send', $order->id) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary">Resend link</button>
                                </form>
                            @elseif($stage === 'collected' && $order->signature_path)
                                <img src="{{ url('public/'.$order->signature_path) }}" alt="Signature" style="height:48px;background:#fff;border:1px solid #eee;">
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
@else
    <div class="laundry-empty">{{ $empty }}</div>
@endif

@if($stage === 'received')
<script>
(function () {
    if (window.__laundryCollectSync) return;
    window.__laundryCollectSync = true;
    document.addEventListener('change', function (e) {
        if (!e.target.classList || !e.target.classList.contains('collect-all')) return;
        var block = e.target.closest('.laundry-order-block');
        if (!block) return;
        block.querySelectorAll('.item-collect').forEach(function (el) { el.value = e.target.value; });
    });
})();
</script>
@endif
