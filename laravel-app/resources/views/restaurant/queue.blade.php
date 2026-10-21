@extends('layout.main')
@section('content')
    <section class="container-fluid">
        @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
        @if(session('not_permitted'))<div class="alert alert-danger">{{ session('not_permitted') }}</div>@endif
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th>Order</th>
                    <th>Guest</th>
                    <th>Service</th>
                    <th>Item</th>
                    <th>Waitress</th>
                    <th>Time</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($items as $item)
                    @php $order = $item->order; $sale = $order ? $order->sale : null; @endphp
                    <tr>
                        <td>{{ $sale->reference_no ?? $item->restaurant_order_id }}</td>
                        <td>{{ optional(optional($sale)->customer)->name }}</td>
                        <td>{{ $order ? $order->serviceLabel() : '' }}</td>
                        <td>
                            {{ $item->product_name }} × {{ $item->qty }}
                            @php
                                $statusLabels = ['awaiting' => 'Awaiting', 'preparing' => 'Accepted', 'ready' => 'Ready for Delivery', 'delivered' => 'Delivered'];
                                $canAct = \App\Services\Restaurant\RestaurantAccess::canItem(auth()->user(), $item);
                            @endphp
                            <div class="text-muted">{{ \App\Services\Restaurant\RestaurantDepartment::label($item->lane ?: $item->department) }} · {{ $statusLabels[$item->status] ?? $item->status }}
                                @if($item->attempt_no > 1) · attempt {{ $item->attempt_no }} @endif
                            </div>
                            @if($item->customer_comment)<div>{{ $item->customer_comment }}</div>@endif
                        </td>
                        <td>{{ optional(optional($order)->waitress)->name ?: 'Unassigned' }}</td>
                        <td>
                            @if($item->ready_at)
                                Ready {{ $item->ready_at->format('H:i') }}
                                · waiting {{ $item->ready_at->diffInMinutes(now()) }} min
                            @elseif($item->expected_ready_at)
                                Due {{ $item->expected_ready_at->format('H:i') }}
                                @if($item->expected_ready_at->isPast() && $item->status === 'preparing')
                                    <div>Taking longer than expected</div>
                                @endif
                            @endif
                        </td>
                        <td style="min-width:220px;">
                            @if($canAct && $item->status === 'awaiting')
                                <form method="post" action="{{ route('restaurant.accept', $item->id) }}">
                                    @csrf
                                    <div class="input-group">
                                        <input class="form-control" type="number" name="minutes" min="1" max="240" placeholder="Minutes" required>
                                        <span class="input-group-btn"><button class="btn btn-primary" type="submit">Accept</button></span>
                                    </div>
                                </form>
                            @endif
                            @if($canAct && $item->status === 'preparing')
                                <form method="post" action="{{ route('restaurant.ready.mark', $item->id) }}" style="margin-bottom:6px;">
                                    @csrf
                                    <button class="btn btn-success btn-sm" type="submit">Mark ready</button>
                                </form>
                                <form method="post" action="{{ route('restaurant.estimate', $item->id) }}">
                                    @csrf
                                    <div class="input-group">
                                        <input class="form-control" type="number" name="minutes" min="1" max="240" placeholder="New minutes" required>
                                        <span class="input-group-btn"><button class="btn btn-default btn-sm" type="submit">Update time</button></span>
                                    </div>
                                </form>
                            @endif
                            @if($item->status === 'ready' && ($canAct || $canWait))
                                <form method="post" action="{{ route('restaurant.deliver', $item->id) }}">
                                    @csrf
                                    <button class="btn btn-primary btn-sm" type="submit">Delivered</button>
                                </form>
                            @endif
                            @if($canReject && $item->status === 'delivered')
                                <form method="post" action="{{ route('restaurant.reject', $item->id) }}" style="margin-top:8px;">
                                    @csrf
                                    <input class="form-control" name="qty" type="number" min="0.01" max="{{ $item->qty }}" step="0.01" value="{{ $item->qty }}" required>
                                    <input class="form-control" name="reason" placeholder="Reason" required style="margin-top:4px;">
                                    <select class="form-control" name="resolution" style="margin-top:4px;">
                                        <option value="remake">Remake</option>
                                        <option value="replacement">Replacement</option>
                                        <option value="cancellation">Cancellation</option>
                                        <option value="price_adjustment">Approved price adjustment</option>
                                        <option value="refund">Refund</option>
                                    </select>
                                    <button class="btn btn-warning btn-sm" style="margin-top:4px;" type="submit">Record rejection</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">Nothing in this tab.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <script>setTimeout(function () { window.location.reload(); }, 15000);</script>
@endsection
