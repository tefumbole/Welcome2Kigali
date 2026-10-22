@extends('restaurant.layout')
@section('title', 'Track '.$sale->reference_no)
@section('content')
    <h1>{{ $sale->reference_no }}</h1>
    <p>{{ $order->serviceLabel() }} · {{ (int)$sale->payment_status === 4 ? 'Paid' : ((int)$sale->payment_status === 3 ? 'Partly paid' : 'Unpaid') }}</p>
    @php
        $food = $order->items->where('department', 'food');
        $drinks = $order->items->where('department', 'drinks');
        $open = $order->items->filter(function ($item) {
            return in_array($item->status, ['awaiting', 'preparing'], true) && $item->expected_ready_at;
        });
        $latest = null;
        foreach ($open as $item) {
            if (! $latest || $item->expected_ready_at->gt($latest)) {
                $latest = $item->expected_ready_at;
            }
        }
        $statusLabels = ['awaiting' => 'Awaiting', 'preparing' => 'Accepted', 'ready' => 'Ready for delivery', 'delivered' => 'Delivered', 'rejected' => 'Not accepted'];
        $anyDelivered = $order->items->contains(function ($item) {
            return $item->status === 'delivered';
        });
    @endphp
    <div class="card" id="countdown" data-ready="{{ $latest ? $latest->toIso8601String() : '' }}">
        @if($food->where('status', 'preparing')->count())<span class="steam" title="Cooking"></span> Kitchen @endif
        @if($drinks->whereIn('status', ['awaiting', 'preparing'])->count())<span class="glass" title="Preparing"></span> Bar @endif
        <p id="count-text" class="{{ $latest ? '' : 'waiting' }}">@if($latest) Expected {{ $latest->format('H:i') }} @elseif($open->isNotEmpty() || $order->items->where('status', 'preparing')->count()) Accepted @else Waiting for the kitchen or bar to accept the order. @endif</p>
    </div>
    @foreach(['Food' => $food, 'Drinks' => $drinks] as $label => $rows)
        <div class="card">
            <h2>{{ $label }}</h2>
            @forelse($rows as $item)
                <p><strong>{{ $item->product_name }}</strong> × {{ $item->qty }} — {{ $statusLabels[$item->status] ?? ucfirst($item->status) }}
                    @if($item->status === 'preparing' && $item->expected_ready_at)
                        · ready about {{ $item->expected_ready_at->format('H:i') }}
                    @endif
                </p>
                @if($item->customer_comment)<p class="muted">{{ $item->customer_comment }}</p>@endif
                @if($item->awaitingSignature())
                    <form method="post" action="{{ route('restaurant.track.sign', [$sale->id, $order->token, $item->id]) }}" class="w2k-sign">
                        @csrf
                        <p><strong>
                            @if($item->proposed_price !== null)
                                New price: {{ number_format((float) $item->proposed_price, 0) }} each. Sign to accept it. Until you sign, the original price stays.
                            @else
                                Please confirm this change.
                            @endif
                        </strong></p>
                        @if($item->proposed_note)<p class="muted">{{ $item->proposed_note }}</p>@endif
                        <label>Your name</label>
                        <input name="guest_name" required maxlength="191" value="{{ optional($sale->customer)->name }}">
                        <label>Signature</label>
                        <canvas class="sign-pad" width="640" height="160" style="width:100%;height:160px;border:1px solid #e4d7c3;border-radius:12px;background:#fff;touch-action:none;"></canvas>
                        <input type="hidden" name="signature" class="sign-data">
                        <p class="row" style="margin-top:8px;">
                            <button type="button" class="btn ghost sign-clear">Clear</button>
                            <button type="submit">Sign</button>
                        </p>
                    </form>
                @elseif($item->client_signed_at && ($item->proposed_price !== null || $item->proposed_note))
                    <p class="muted">Signed {{ $item->client_signed_at->format('d M Y H:i') }}@if($item->proposed_price !== null) · {{ number_format((float) $item->proposed_price, 0) }} each @endif</p>
                @endif
            @empty
                <p class="muted">No {{ strtolower($label) }} on this order.</p>
            @endforelse
        </div>
    @endforeach
    @if($order->customer_comment)<p class="muted">Your note: {{ $order->customer_comment }}</p>@endif
    <p>
        @if((int)$sale->payment_status === 4)
            <strong>Paid.</strong>
        @else
            <a class="btn" href="{{ $payUrl }}">Pay</a>
        @endif
        @if($anyDelivered || $order->isFulfilled())
            <a class="btn ghost" href="{{ url('/reviews?ref='.urlencode($sale->reference_no)) }}">Add review</a>
        @endif
    </p>
    <script>
        (function () {
            var box = document.getElementById('countdown');
            var label = document.getElementById('count-text');
            var ready = box.getAttribute('data-ready');
            if (!ready) return;
            var end = new Date(ready).getTime();
            function tick() {
                var left = end - Date.now();
                if (left <= 0) {
                    label.textContent = 'Taking longer than expected';
                    label.className = 'waiting';
                    return;
                }
                label.className = '';
                var mins = Math.floor(left / 60000);
                var secs = Math.floor((left % 60000) / 1000);
                label.textContent = mins + 'm ' + (secs < 10 ? '0' : '') + secs + 's';
            }
            tick();
            setInterval(tick, 1000);
        })();
        (function () {
            var forms = document.querySelectorAll('.w2k-sign');
            Array.prototype.forEach.call(forms, function (form) {
                var canvas = form.querySelector('.sign-pad');
                var hidden = form.querySelector('.sign-data');
                var clear = form.querySelector('.sign-clear');
                if (!canvas || !hidden) return;
                var ctx = canvas.getContext('2d');
                var drawing = false;
                function pos(e) {
                    var r = canvas.getBoundingClientRect();
                    var src = e.touches ? e.touches[0] : e;
                    return {
                        x: (src.clientX - r.left) * (canvas.width / r.width),
                        y: (src.clientY - r.top) * (canvas.height / r.height)
                    };
                }
                function start(e) {
                    drawing = true;
                    canvas.className = 'sign-pad drawing';
                    var p = pos(e);
                    ctx.beginPath();
                    ctx.moveTo(p.x, p.y);
                    e.preventDefault();
                }
                function move(e) {
                    if (!drawing) return;
                    var p = pos(e);
                    ctx.lineWidth = 2.5;
                    ctx.lineCap = 'round';
                    ctx.strokeStyle = '#0A0A0A';
                    ctx.lineTo(p.x, p.y);
                    ctx.stroke();
                    e.preventDefault();
                }
                function stop() { drawing = false; canvas.className = 'sign-pad'; }
                canvas.addEventListener('mousedown', start);
                canvas.addEventListener('mousemove', move);
                window.addEventListener('mouseup', stop);
                canvas.addEventListener('touchstart', start, {passive: false});
                canvas.addEventListener('touchmove', move, {passive: false});
                canvas.addEventListener('touchend', stop);
                if (clear) clear.addEventListener('click', function () {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    hidden.value = '';
                });
                form.addEventListener('submit', function (e) {
                    var blank = document.createElement('canvas');
                    blank.width = canvas.width;
                    blank.height = canvas.height;
                    if (canvas.toDataURL() === blank.toDataURL()) {
                        e.preventDefault();
                        window.alert('Please sign in the box.');
                        return;
                    }
                    hidden.value = canvas.toDataURL('image/png');
                });
            });
        })();
        setTimeout(function () {
            if (document.querySelector('.sign-pad.drawing')) return;
            var active = document.activeElement;
            if (active && /^(INPUT|TEXTAREA)$/.test(active.tagName)) return;
            window.location.reload();
        }, 20000);
    </script>
@endsection
