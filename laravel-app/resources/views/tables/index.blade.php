@extends('layout.main')
@section('content')
<section class="container-fluid" style="max-width:960px;">
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if(session('not_permitted'))<div class="alert alert-danger">{{ session('not_permitted') }}</div>@endif

    <div style="background:#0A0A0A;color:#F7F1E8;border-radius:18px;padding:22px 24px;margin-bottom:18px;">
        <p style="margin:0 0 6px;color:#C5A059;letter-spacing:.1em;text-transform:uppercase;font-size:11px;">Dining</p>
        <h1 style="margin:0;font-size:1.7rem;">Tables</h1>
        <p style="margin:8px 0 0;color:#e8d7b0;">Add tables for dine-in POS orders. Staff pick a table when they sell.</p>
    </div>

    <div class="card mb-4" style="border:1px solid #ead9b4;border-radius:16px;">
        <div class="card-body">
            <h3 style="margin-top:0;">Add new table</h3>
            <form method="POST" action="{{ route('tables.store') }}" class="row">
                @csrf
                <div class="col-md-3 form-group">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Table 1" required>
                </div>
                <div class="col-md-4 form-group">
                    <label>Description</label>
                    <input type="text" name="description" class="form-control" placeholder="Near window, patio…">
                </div>
                <div class="col-md-2 form-group">
                    <label>Persons</label>
                    <input type="number" name="capacity" class="form-control" min="1" max="100" placeholder="4">
                </div>
                <div class="col-md-3 form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-block" style="background:#C5A059;color:#0A0A0A;font-weight:700;">Add table</button>
                </div>
            </form>
        </div>
    </div>

    <div style="background:#fff;border:1px solid #ead9b4;border-radius:16px;overflow:hidden;">
        @forelse($tables as $table)
            <div class="row align-items-center" style="margin:0;padding:12px 14px;border-bottom:1px solid #f0e4c8;">
                <div class="col-md-10">
                    <form method="POST" action="{{ route('tables.update', $table->id) }}" class="row align-items-center">
                        @csrf
                        @method('PUT')
                        <div class="col-md-3"><input type="text" name="name" class="form-control" value="{{ $table->name }}" required></div>
                        <div class="col-md-4"><input type="text" name="description" class="form-control" value="{{ $table->description }}"></div>
                        <div class="col-md-2"><input type="number" name="capacity" class="form-control" min="1" value="{{ $table->capacity }}"></div>
                        <div class="col-md-3">
                            <label style="font-weight:600;margin:0 12px 0 0;">
                                <input type="checkbox" name="is_active" value="1" @if($table->is_active) checked @endif> Active
                            </label>
                            <button type="submit" class="btn btn-sm btn-primary">Save</button>
                        </div>
                    </form>
                </div>
                <div class="col-md-2 text-right">
                    <form method="POST" action="{{ route('tables.destroy', $table->id) }}" onsubmit="return confirm('Remove this table?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-center text-muted" style="padding:28px;margin:0;">No tables yet. Add Table 1, Table 2…</p>
        @endforelse
    </div>
</section>
<script>
    $("ul#tables-module").siblings('a').attr('aria-expanded','true');
    $("ul#tables-module").addClass("show");
    $("ul#tables-module #tables-list-menu").addClass("active");
</script>
@endsection
