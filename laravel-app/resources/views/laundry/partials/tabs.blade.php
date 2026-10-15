@php
    $tab = $tab ?? '';
@endphp
<script>
    $("ul#laundry-module").siblings('a').attr('aria-expanded','true');
    $("ul#laundry-module").addClass("show");
    $("ul#laundry-module #laundry-{{ $tab }}-menu").addClass("active");
</script>
<style>
    .laundry-hero {
        background: linear-gradient(135deg, #0A0A0A 0%, #2a2114 55%, #C5A059 160%);
        color: #F7F1E8;
        border-radius: 18px;
        padding: 22px 24px 18px;
        margin-bottom: 18px;
    }
    .laundry-hero h1 { margin: 0 0 6px; font-size: 1.7rem; font-weight: 700; color: #fff; }
    .laundry-hero p { margin: 0; color: #e8d7b0; max-width: 52rem; }
    .laundry-kicker {
        display: inline-block;
        background: rgba(197,160,89,.18);
        color: #C5A059;
        font-size: 11px;
        letter-spacing: .1em;
        text-transform: uppercase;
        padding: 3px 10px;
        border-radius: 999px;
        margin-bottom: 8px;
    }
    .laundry-customer-list {
        border: 1px solid #eee4d0;
        border-radius: 16px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 10px 28px rgba(10,10,10,.05);
    }
    .laundry-customer-row { border-bottom: 1px solid #f3ead6; }
    .laundry-customer-row:last-child { border-bottom: 0; }
    .laundry-customer-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        margin: 0;
        padding: 16px 18px;
        border: 0;
        background: #fff;
        text-align: left;
        color: #1a1a1a;
        cursor: pointer;
    }
    .laundry-customer-btn:hover,
    .laundry-customer-btn:focus { background: #faf6ee; outline: none; }
    .laundry-customer-btn[aria-expanded="true"] { background: #faf6ee; }
    .laundry-customer-name { font-weight: 700; font-size: 16px; letter-spacing: .01em; }
    .laundry-customer-meta { color: #6b7280; font-size: 13px; font-weight: 500; }
    .laundry-customer-btn .chev { margin-left: auto; color: #C5A059; transition: transform .15s ease; }
    .laundry-customer-btn[aria-expanded="true"] .chev { transform: rotate(180deg); }
    .laundry-customer-body { padding: 0 18px 18px; background: #fffdf8; }
    .laundry-order-block {
        background: #fff;
        border: 1px solid #eee4d0;
        border-radius: 12px;
        padding: 14px;
    }
    .laundry-order-block + .laundry-order-block { margin-top: 12px; }
    .laundry-comment {
        background: #fff8ea;
        border-left: 3px solid #C5A059;
        padding: 8px 12px;
        border-radius: 0 8px 8px 0;
        margin: 0 0 12px;
        font-size: 13px;
    }
    .laundry-customer-list .table { margin-bottom: 12px; }
    .laundry-customer-list .table th { border-top: 0; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; }
    .laundry-empty {
        border: 1px dashed #ead9b4;
        border-radius: 16px;
        padding: 36px 20px;
        text-align: center;
        color: #7a6238;
        background: #fffdf8;
    }
    .laundry-stamp {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #0A0A0A;
        background: #F7F1E8;
        border: 1px dashed #C5A059;
        padding: 3px 10px;
        border-radius: 999px;
        white-space: nowrap;
    }
    .laundry-stamp strong { color: #C5A059; font-weight: 700; }
    .laundry-bulk-bar {
        position: sticky;
        bottom: 12px;
        z-index: 5;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        background: #0A0A0A;
        color: #F7F1E8;
        border-radius: 14px;
        padding: 12px 16px;
        margin-top: 14px;
        box-shadow: 0 12px 30px rgba(10,10,10,.18);
    }
    .laundry-bulk-bar .btn-gold {
        background: #C5A059;
        color: #0A0A0A;
        border: 0;
        font-weight: 700;
        border-radius: 999px;
        padding: 8px 18px;
    }
    .laundry-bulk-bar .btn-gold:disabled { opacity: .45; }
    .laundry-check {
        width: 18px;
        height: 18px;
        accent-color: #C5A059;
    }
</style>
