@extends('layout.main')

@section('content')
@php $tab = 'received'; @endphp
<section class="forms">
    <div class="container-fluid">
        @include('laundry.partials.tabs')
        <h1 class="mb-3">Laundry — Received</h1>
        @include('laundry.partials.flash')

        <div class="card mb-4">
            <div class="card-header"><strong>New drop-off</strong></div>
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
                        <button type="submit" class="btn btn-primary">Submit for cleaning</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Waiting for cleaning</strong></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Payment</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td><a href="{{ route('laundry.show', $order->id) }}">{{ $order->reference_no }}</a></td>
                            <td>{{ optional($order->customer)->name }}</td>
                            <td>@include('laundry.partials.items')</td>
                            <td>
                                @if($order->isPaid())
                                    <span class="badge badge-success">Paid</span>
                                @else
                                    <span class="badge badge-warning">Due {{ number_format($order->dueAmount(), 0) }}</span>
                                @endif
                            </td>
                            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('laundry.show', $order->id) }}">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No received tickets.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $orders->links() }}
    </div>
</section>
<script>
(function () {
    var priceMap = {};
    @foreach($products as $p)
    priceMap[{{ $p->id }}] = {{ (float) $p->price }};
    @endforeach

    function rowHtml() {
        return document.querySelector('#garment-table tbody tr').outerHTML;
    }

    function recalc() {
        var total = 0;
        document.querySelectorAll('.garment-row').forEach(function (row) {
            var sel = row.querySelector('.garment-product');
            var qty = parseFloat(row.querySelector('.garment-qty').value) || 0;
            var id = sel.value;
            var unit = priceMap[id] || 0;
            var line = unit * qty;
            row.querySelector('.garment-price').textContent = id ? line.toLocaleString() : '—';
            total += line;
        });
        document.getElementById('laundry-total').textContent = total.toLocaleString();
    }

    document.getElementById('add-row').addEventListener('click', function () {
        var tbody = document.querySelector('#garment-table tbody');
        tbody.insertAdjacentHTML('beforeend', rowHtml());
        recalc();
    });
    document.getElementById('garment-table').addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-row')) {
            var rows = document.querySelectorAll('.garment-row');
            if (rows.length > 1) e.target.closest('tr').remove();
            recalc();
        }
    });
    document.getElementById('garment-table').addEventListener('change', recalc);
    document.getElementById('garment-table').addEventListener('input', recalc);

    var customer = document.getElementById('laundry-customer');
    if (customer) {
        customer.addEventListener('change', function () {
            var id = this.value;
            document.getElementById('member-hint').textContent = '';
            if (!id) return;
            fetch('{{ route('laundry.customer-price') }}?customer_id=' + encodeURIComponent(id), { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    (data.products || []).forEach(function (p) {
                        priceMap[p.id] = p.unit_price;
                    });
                    var discounted = (data.products || []).some(function (p) { return p.unit_price < p.list_price; });
                    document.getElementById('member-hint').textContent = discounted ? 'Member discount applied.' : '';
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
