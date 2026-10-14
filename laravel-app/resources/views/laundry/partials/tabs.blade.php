@php
    $tab = $tab ?? '';
@endphp
<script>
    $("ul#laundry-module").siblings('a').attr('aria-expanded','true');
    $("ul#laundry-module").addClass("show");
    $("ul#laundry-module #laundry-{{ $tab }}-menu").addClass("active");
</script>
<style>
    .laundry-customer-list { border: 1px solid #eee4d0; border-radius: 12px; overflow: hidden; background: #fff; }
    .laundry-customer-row { border-bottom: 1px solid #f3ead6; }
    .laundry-customer-row:last-child { border-bottom: 0; }
    .laundry-customer-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        margin: 0;
        padding: 14px 16px;
        border: 0;
        background: #fff;
        text-align: left;
        color: #1a1a1a;
        cursor: pointer;
    }
    .laundry-customer-btn:hover,
    .laundry-customer-btn:focus { background: #faf6ee; outline: none; }
    .laundry-customer-btn[aria-expanded="true"] { background: #faf6ee; }
    .laundry-customer-name { font-weight: 700; font-size: 15px; }
    .laundry-customer-meta { color: #6b7280; font-size: 13px; font-weight: 500; }
    .laundry-customer-btn .chev { margin-left: auto; color: #8a7424; transition: transform .15s ease; }
    .laundry-customer-btn[aria-expanded="true"] .chev { transform: rotate(180deg); }
    .laundry-customer-body { padding: 0 16px 16px; background: #fff; }
    .laundry-order-block + .laundry-order-block { margin-top: 16px; padding-top: 16px; border-top: 1px dashed #eee4d0; }
    .laundry-customer-list .table { margin-bottom: 12px; }
    .laundry-customer-list .table th { border-top: 0; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; }
</style>
