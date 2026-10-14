@extends('layout.main')

@section('content')
@php
    $tab = 'received';
    $defaultCollect = now()->addDay()->setTime(17, 0)->format('Y-m-d\TH:i');
@endphp
<section class="forms">
    <div class="container-fluid">
        @include('laundry.partials.tabs')
        <h1 class="mb-3">Laundry — Awaiting Acceptance</h1>
        @include('laundry.partials.flash')
        <p class="text-muted">Dresses forwarded from POS wait here. Select each dress, set the collection date and time, then accept. The client is notified and the ticket moves to Awaiting cleaning.</p>

        @forelse($orders as $order)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><a href="{{ route('laundry.show', $order->id) }}">{{ $order->reference_no }}</a></strong>
                <span>{{ optional($order->customer)->name }} · {{ optional($order->customer)->phone_number }}</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('laundry.accept', $order->id) }}">
                    @csrf
                    <div class="form-row align-items-end mb-2">
                        <div class="form-group col-md-4 mb-2">
                            <label>Collection date &amp; time for all selected</label>
                            <input type="datetime-local" name="collect_at" class="form-control collect-all" value="{{ $defaultCollect }}" required>
                        </div>
                        <div class="form-group col-md-4 mb-2">
                            <label>Payment</label>
                            <div>
                                @if($order->isPaid())
                                    <span class="badge badge-success">Paid</span>
                                @else
                                    <span class="badge badge-warning">Due {{ number_format($order->dueAmount(), 0) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th style="width:40px;">Select</th>
                                <th>Dress</th>
                                <th>Qty</th>
                                <th>Collect by</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td><input type="checkbox" name="item_id[]" value="{{ $item->id }}" checked></td>
                                <td>{{ $item->name }}@if($item->note) <small class="text-muted">— {{ $item->note }}</small>@endif</td>
                                <td>{{ $item->qty + 0 }}</td>
                                <td><input type="datetime-local" name="item_collect_at[{{ $item->id }}]" class="form-control form-control-sm item-collect" value="{{ $defaultCollect }}"></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <button type="submit" class="btn btn-primary">Accept &amp; send to cleaning</button>
                </form>
            </div>
        </div>
        @empty
        <div class="alert alert-light border">No dresses awaiting acceptance.</div>
        @endforelse
        {{ $orders->links() }}

        <div class="card mt-4">
            <div class="card-header"><strong>Walk-in drop-off</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('laundry.received.store') }}" id="laundry-intake">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Customer *</label>
                            <select name="customer_id" id="laundry-customer" class="form-control selectpicker" data-live-search="true" required>
                                <option value="">Select customer</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} {{ $c->phone_number ? '· '.$c->phone_number : '' }}</option>
                                @endforeach
                            </select>
                            <small id="member-hint" class="text-muted"></small>
                        </div>
                        <div class="form-group col-md-6">
                            <label>General note</label>
                            <input type="text" name="notes" class="form-control" placeholder="Stain on collar, no starch, …">
                        </div>
                    </div>
                    <table class="table table-sm" id="garment-table">
                        <thead>
                            <tr>
                                <th>Dress</th>
                                <th style="width:90px;">Qty</th>
                                <th>Price</th>
                                <th>Note for this dress</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="garment-row">
                                <td>
                                    <select name="product_id[]" class="form-control garment-product">
                                        <option value="">Select</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}" data-price="{{ $p->price }}">{{ $p->name }} — {{ number_format($p->price, 0) }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" name="qty[]" class="form-control garment-qty" min="1" step="1" value="1"></td>
                                <td class="garment-price text-nowrap">—</td>
                                <td><input type="text" name="item_note[]" class="form-control" placeholder="Optional"></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="add-row">Add dress</button>
                    <div class="mt-3 d-flex flex-wrap align-items-center" style="gap:12px;">
                        <label class="mb-0">
                            <input type="checkbox" name="pay_now" value="1"> Pay now (cash)
                        </label>
                        <strong>Total: <span id="laundry-total">0</span></strong>
                        <button type="submit" class="btn btn-primary">Submit for acceptance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
<script>
(function () {
    document.querySelectorAll('.collect-all').forEach(function (input) {
        input.addEventListener('change', function () {
            var card = this.closest('.card');
            if (!card) return;
            card.querySelectorAll('.item-collect').forEach(function (el) { el.value = input.value; });
        });
    });

    var priceMap = {};
    @foreach($products as $p)
    priceMap[{{ $p->id }}] = {{ (float) $p->price }};
    @endforeach

    function rowHtml() {
        var first = document.querySelector('#garment-table tbody tr');
        return first ? first.outerHTML : '';
    }

    function recalc() {
        var total = 0;
        document.querySelectorAll('.garment-row').forEach(function (row) {
            var sel = row.querySelector('.garment-product');
            var qtyEl = row.querySelector('.garment-qty');
            if (!sel || !qtyEl) return;
            var qty = parseFloat(qtyEl.value) || 0;
            var id = sel.value;
            var unit = priceMap[id] || 0;
            var line = unit * qty;
            var priceCell = row.querySelector('.garment-price');
            if (priceCell) priceCell.textContent = id ? line.toLocaleString() : '—';
            total += line;
        });
        var tot = document.getElementById('laundry-total');
        if (tot) tot.textContent = total.toLocaleString();
    }

    var addBtn = document.getElementById('add-row');
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            document.querySelector('#garment-table tbody').insertAdjacentHTML('beforeend', rowHtml());
            recalc();
        });
    }
    var table = document.getElementById('garment-table');
    if (table) {
        table.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-row')) {
                var rows = document.querySelectorAll('.garment-row');
                if (rows.length > 1) e.target.closest('tr').remove();
                recalc();
            }
        });
        table.addEventListener('change', recalc);
        table.addEventListener('input', recalc);
    }

    var customer = document.getElementById('laundry-customer');
    if (customer) {
        customer.addEventListener('change', function () {
            var id = this.value;
            var hint = document.getElementById('member-hint');
            if (hint) hint.textContent = '';
            if (!id) return;
            fetch('{{ route('laundry.customer-price') }}?customer_id=' + encodeURIComponent(id), { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    (data.products || []).forEach(function (p) {
                        priceMap[p.id] = p.unit_price;
                    });
                    var discounted = (data.products || []).some(function (p) { return p.unit_price < p.list_price; });
                    if (hint) hint.textContent = discounted ? 'Member discount applied.' : '';
                    document.querySelectorAll('.garment-product option').forEach(function (opt) {
                        var pid = opt.value;
                        if (!pid || !priceMap[pid]) return;
                        var name = opt.textContent.split(' — ')[0];
                        opt.textContent = name + ' — ' + Number(priceMap[pid]).toLocaleString();
                    });
                    recalc();
                });
        });
    }
    recalc();
})();
</script>
@endsection
