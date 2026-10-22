@extends('layout.main')
@section('content')
    <style>
        .w2k-board { display:flex; flex-direction:column; gap:14px; }
        .w2k-ticket {
            background:#fff; border:1px solid #eadfcd; border-radius:18px;
            box-shadow:0 10px 28px rgba(10,10,10,.05);
            padding:16px 18px; display:grid; grid-template-columns:minmax(160px,220px) minmax(220px,1fr) minmax(220px,280px);
            gap:16px; align-items:start;
        }
        .w2k-kicker { margin:0; font-size:12px; letter-spacing:.14em; text-transform:uppercase; color:#8a7350; }
        .w2k-ref { margin:4px 0 0; font-size:18px; font-weight:700; color:#0A0A0A; }
        .w2k-guest { margin:4px 0 0; color:#3d3428; }
        .w2k-meta { margin:8px 0 0; color:#6d6256; font-size:13px; }
        .w2k-item { margin:0; font-size:20px; line-height:1.25; color:#0A0A0A; }
        .w2k-pill {
            display:inline-block; margin-top:8px; padding:4px 10px; border-radius:999px;
            background:#f6f0e4; color:#6a5420; font-size:12px; font-weight:700; letter-spacing:.04em;
        }
        .w2k-pill.late { background:#fde8e8; color:#8a1f1f; }
        .w2k-head { display:flex; justify-content:space-between; align-items:flex-end; gap:12px; padding:4px 4px 0; }
        .w2k-head h1 { margin:2px 0 0; font-size:28px; font-weight:700; color:#0A0A0A; }
        .w2k-actions .form-control { border-radius:10px; border-color:#eadfcd; }
        .w2k-actions .btn-primary { background:#C5A059; border-color:#C5A059; color:#0A0A0A; font-weight:700; border-radius:999px; }
        .w2k-actions .btn-success { border-radius:999px; font-weight:700; }
        .w2k-row { display:flex; flex-wrap:wrap; gap:8px; }
        .w2k-choice { margin:0; }
        .w2k-choice summary {
            list-style:none; cursor:pointer; display:inline-block;
            border:1px solid #0A0A0A; color:#0A0A0A; background:#fff;
            border-radius:999px; padding:7px 12px; font-size:13px; font-weight:700;
        }
        .w2k-choice summary::-webkit-details-marker { display:none; }
        .w2k-choice.deny summary { border-color:#8a1f1f; color:#8a1f1f; }
        .w2k-choice form { margin-top:8px; }
        .w2k-note { margin:8px 0 0; padding:8px 10px; border-radius:12px; background:#f6f0e4; color:#6a5420; font-size:13px; }
        .w2k-note.signed { background:#e7f6ea; color:#1d6b32; }
        .w2k-empty { background:#fff; border:1px dashed #e4d7c3; border-radius:18px; padding:28px; color:#6d6256; }
        .w2k-reject { margin-top:8px; }
        .w2k-reject summary { cursor:pointer; color:#8a7350; font-size:13px; }
        @media (max-width:900px) {
            .w2k-ticket { grid-template-columns:1fr; }
        }
    </style>
    <section class="container-fluid">
        @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
        @if(session('not_permitted'))<div class="alert alert-danger">{{ session('not_permitted') }}</div>@endif
        <header class="w2k-head">
            <div>
                <p class="w2k-kicker">Orders</p>
                <h1>{{ $lanes[$lane] ?? 'Orders' }} · {{ $tabs[$tab] ?? '' }}</h1>
            </div>
            <div class="w2k-meta" style="text-align:right;">
                <p style="margin:0;">{{ $items->count() }} on this screen</p>
                <a href="{{ route('help.index', ['topic' => \App\Services\Restaurant\RestaurantAccess::helpHashForLane($lane)]) }}"
                   class="btn btn-default btn-sm" style="margin-top:8px;border-radius:999px;border-color:#C5A059;color:#6a5420;">
                    Help · {{ $lanes[$lane] ?? 'Orders' }}
                </a>
            </div>
        </header>
        <div class="w2k-board">
            @forelse($items as $item)
                @php
                    $order = $item->order;
                    $sale = $order ? $order->sale : null;
                    $statusLabels = ['awaiting' => 'Awaiting', 'preparing' => 'Accepted', 'ready' => 'Ready for Delivery', 'hold' => 'On Hold', 'delivered' => 'Delivered'];
                    $canAdd = \App\Services\Restaurant\RestaurantAccess::canOrder(auth()->user(), 'add', $item);
                    $canEdit = \App\Services\Restaurant\RestaurantAccess::canOrder(auth()->user(), 'edit', $item);
                    $canDelete = \App\Services\Restaurant\RestaurantAccess::canOrder(auth()->user(), 'delete', $item);
                    $late = $item->expected_ready_at && $item->expected_ready_at->isPast() && $item->status === 'preparing';
                @endphp
                <article class="w2k-ticket">
                    <div>
                        <p class="w2k-kicker">{{ $order ? $order->serviceLabel() : 'Order' }}</p>
                        <p class="w2k-ref">{{ $sale->reference_no ?? $item->restaurant_order_id }}</p>
                        <p class="w2k-guest">{{ optional(optional($sale)->customer)->name ?: 'Guest' }}</p>
                        <p class="w2k-meta">
                            Placed by {{ optional(optional($order)->placedBy)->name ?: optional(optional($order)->waitress)->name ?: 'Staff' }}
                            @if(optional($order)->diningTable)
                                · {{ $order->diningTable->name }}
                                @if($order->diningTable->capacity) ({{ $order->diningTable->capacity }}p) @endif
                            @endif
                        </p>
                        @if(optional($order)->waitress && optional($order)->placed_by_user_id != optional($order)->waitress_user_id)
                            <p class="w2k-meta">Waitress: {{ $order->waitress->name }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="w2k-item">{{ $item->product_name }} <span style="color:#8a7350;">× {{ $item->qty }}</span></p>
                        <span class="w2k-pill {{ $late ? 'late' : '' }}">
                            {{ \App\Services\Restaurant\RestaurantDepartment::label($item->lane ?: $item->department) }}
                            · {{ $statusLabels[$item->status] ?? $item->status }}
                            @if($item->attempt_no > 1) · attempt {{ $item->attempt_no }} @endif
                        </span>
                        @if($item->customer_comment)<p class="w2k-note">Comment: {{ $item->customer_comment }}</p>@endif
                        @if($item->status === 'hold' && $item->internal_note)<p class="w2k-note">Hold: {{ $item->internal_note }}</p>@endif
                        <p class="w2k-meta">
                            @if($item->ready_at)
                                Ready {{ $item->ready_at->format('H:i') }} · waiting {{ $item->ready_at->diffInMinutes(now()) }} min
                            @elseif($item->expected_ready_at)
                                Due {{ $item->expected_ready_at->format('H:i') }}
                                @if($late) · taking longer than expected @endif
                            @elseif($item->status === 'preparing')
                                No time given
                            @endif
                        </p>
                    </div>
                    <div class="w2k-actions">
                        @if($item->productSale)
                            <p class="w2k-meta">{{ number_format((float) $item->productSale->total, 0) }} RWF</p>
                        @endif
                        @if($item->awaitingSignature())
                            <p class="w2k-note">
                                @if($item->proposed_price !== null)
                                    New price {{ number_format((float) $item->proposed_price, 0) }} each is waiting for the client's signature.
                                @else
                                    A change is waiting for the client's signature.
                                @endif
                                @if($item->proposed_note) {{ $item->proposed_note }} @endif
                            </p>
                        @elseif($item->client_signed_at)
                            <p class="w2k-note signed">Client signed {{ $item->client_signed_at->format('H:i') }}.</p>
                        @endif
                        @if($item->status === 'awaiting' && ($canAdd || $canDelete))
                            <div class="w2k-row" style="margin-bottom:8px;">
                                @if($canAdd)
                                    <form method="post" action="{{ route('restaurant.accept', $item->id) }}" style="display:inline;">
                                        @csrf
                                        <button class="btn btn-primary" type="submit">Accept</button>
                                    </form>
                                @endif
                                @if($canDelete)
                                    <form method="post" action="{{ route('restaurant.deny', $item->id) }}" style="display:inline;" onsubmit="return confirm('Reject this order?');">
                                        @csrf
                                        <input type="hidden" name="reason" value="Rejected">
                                        <button class="btn btn-default" type="submit">Reject</button>
                                    </form>
                                @endif
                            </div>
                        @endif
                        @if($canEdit && $item->status === 'preparing')
                            <form method="post" action="{{ route('restaurant.ready.mark', $item->id) }}" style="margin-bottom:8px;">
                                @csrf
                                <button class="btn btn-success btn-block" type="submit">Mark ready</button>
                            </form>
                            <form method="post" action="{{ route('restaurant.estimate', $item->id) }}">
                                @csrf
                                <div class="input-group">
                                    <input class="form-control" type="number" name="minutes" min="1" max="240" placeholder="New minutes" required>
                                    <span class="input-group-btn"><button class="btn btn-default" type="submit">Update time</button></span>
                                </div>
                            </form>
                        @endif
                        @if($item->status === 'ready' && $canEdit)
                            <form method="post" action="{{ route('restaurant.deliver', $item->id) }}">
                                @csrf
                                <button class="btn btn-primary btn-block" type="submit">Delivered</button>
                            </form>
                        @endif
                        @if($canDelete && $item->status === 'delivered')
                            <details class="w2k-reject">
                                <summary>Record rejection</summary>
                                <form method="post" action="{{ route('restaurant.reject', $item->id) }}" style="margin-top:8px;">
                                    @csrf
                                    <input class="form-control" name="qty" type="number" min="0.01" max="{{ $item->qty }}" step="0.01" value="{{ $item->qty }}" required>
                                    <input class="form-control" name="reason" placeholder="Reason" required style="margin-top:6px;">
                                    <select class="form-control" name="resolution" style="margin-top:6px;">
                                        <option value="remake">Remake</option>
                                        <option value="replacement">Replacement</option>
                                        <option value="cancellation">Cancellation</option>
                                        <option value="price_adjustment">Approved price adjustment</option>
                                        <option value="refund">Refund</option>
                                    </select>
                                    <button class="btn btn-warning btn-block" style="margin-top:6px;" type="submit">Record rejection</button>
                                </form>
                            </details>
                        @endif
                    </div>
                </article>
            @empty
                <div class="w2k-empty">Nothing in this tab.</div>
            @endforelse
        </div>
    </section>
@endsection
