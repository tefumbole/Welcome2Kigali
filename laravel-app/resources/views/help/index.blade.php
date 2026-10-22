@extends('layout.main')

@section('content')
@php
    $hv = $appVersion ?? \App\Support\AppVersion::erp();
    $logo = $appLogo ?? \App\Support\SiteBrand::logoUrl();
    $title = $siteTitle ?? 'Welcome 2 Kigali';
    $show = function ($key) use ($topics, $focus) {
        return isset($topics[$key]) && (! $focus || $focus === $key);
    };
    $helpIndexUrl = url('/admin/help');
@endphp
<style>
    .w2k-help { max-width: 1100px; }
    .w2k-help-hero {
        background: linear-gradient(135deg, #1a1a1a 0%, #3a2c16 60%, #c5a059 140%);
        color: #f7f1e8;
        border-radius: 18px;
        padding: 22px 26px 18px;
        margin-bottom: 20px;
        display: flex;
        align-items: flex-start;
        gap: 16px;
        flex-wrap: wrap;
    }
    .w2k-help-hero-logo {
        width: 36px;
        height: 36px;
        object-fit: contain;
        border-radius: 8px;
        background: #fff;
        padding: 3px;
        flex-shrink: 0;
        margin-top: 2px;
    }
    .w2k-help-hero-body { flex: 1; min-width: 200px; }
    .w2k-help-hero h2 { margin: 0 0 6px; font-size: 26px; }
    .w2k-help-hero p { margin: 0; color: #e8d7b0; font-size: 14px; }
    .w2k-help-version {
        margin-left: auto;
        font-size: 12px;
        letter-spacing: .04em;
        color: #c5a059;
        background: rgba(0,0,0,.28);
        border: 1px solid rgba(197,160,89,.45);
        border-radius: 999px;
        padding: 6px 12px;
        align-self: flex-start;
        white-space: nowrap;
    }
    .w2k-help-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 20px;
    }
    .w2k-help-nav a {
        display: inline-block;
        padding: 8px 14px;
        border-radius: 999px;
        background: #fff8ea;
        border: 1px solid #ead9b4;
        color: #5c4630;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
    }
    .w2k-help-nav a:hover, .w2k-help-nav a.is-active { background: #1a1a1a; color: #c5a059; border-color: #1a1a1a; }
    .w2k-help-focus-bar {
        background: #fff8ea;
        border: 1px solid #ead9b4;
        border-radius: 12px;
        padding: 12px 16px;
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        font-size: 14px;
        color: #5c4630;
    }
    .w2k-help-focus-bar a {
        color: #1a1a1a;
        font-weight: 700;
        text-decoration: underline;
    }
    .w2k-help-card {
        background: #fffdf8;
        border: 1px solid #ead9b4;
        border-radius: 16px;
        padding: 22px 24px;
        margin-bottom: 22px;
    }
    .w2k-help-card h3 { margin-top: 0; color: #1a1a1a; }
    .w2k-help-card h4 { color: #5c4630; margin-top: 18px; }
    .w2k-help-card ol, .w2k-help-card ul { padding-left: 1.2rem; }
    .w2k-help-shot {
        display: block;
        width: 100%;
        border-radius: 12px;
        border: 1px solid #e4d3b0;
        margin: 12px 0 6px;
        box-shadow: 0 8px 24px rgba(26,26,26,.08);
    }
    .w2k-help-cap { font-size: 12px; color: #7a6238; margin-bottom: 14px; }
    .w2k-help-table { width: 100%; font-size: 13px; }
    .w2k-help-table th { background: #1a1a1a; color: #c5a059; padding: 8px 10px; }
    .w2k-help-table td { padding: 7px 10px; border-bottom: 1px solid #f0e4c8; }
    .w2k-help-table tr:nth-child(even) td { background: #fff8ea; }
    .w2k-help-kicker {
        display: inline-block;
        background: #1a1a1a;
        color: #c5a059;
        font-size: 11px;
        letter-spacing: .08em;
        text-transform: uppercase;
        padding: 3px 9px;
        border-radius: 999px;
        margin-bottom: 8px;
    }
    .w2k-help-tip {
        background: #fff8ea;
        border-left: 4px solid #c5a059;
        padding: 10px 14px;
        border-radius: 0 10px 10px 0;
        margin: 12px 0;
        font-size: 14px;
    }
</style>

<script>
    (function () {
        var hash = (window.location.hash || '').replace(/^#/, '');
        if (!hash) return;
        var params = new URLSearchParams(window.location.search);
        if (params.get('topic') || params.get('tab')) return;
        params.set('topic', hash);
        window.location.replace(window.location.pathname + '?' + params.toString());
    })();
</script>
<div class="container-fluid w2k-help">
    <div class="w2k-help-hero">
        @if($logo)
            <img class="w2k-help-hero-logo" src="{{ $logo }}" alt="{{ $title }} logo">
        @endif
        <div class="w2k-help-hero-body">
            <span class="w2k-help-kicker">{{ $title }}</span>
            <h2>
                @if($focus && isset($topics[$focus]))
                    {{ $topics[$focus]['title'] }} help
                @else
                    Staff user guide
                @endif
            </h2>
            <p>
                @if($focus && isset($topics[$focus]))
                    This page shows help for <strong>{{ $topics[$focus]['title'] }}</strong> only — opened from that menu’s Help tab.
                @else
                    How to run {{ $title }} — cafe, website, laundry, POS, rentals, quotations, contracts, membership, and accounts. Open Help from the last tab on any module you can access, or from the sidebar.
                @endif
            </p>
        </div>
        <span class="w2k-help-version" title="Application version">{{ $hv }}</span>
    </div>

    @if($focus)
        <div class="w2k-help-focus-bar">
            <span>Focused on this menu’s help.</span>
            <a href="{{ $helpIndexUrl }}">Show all help you can access</a>
        </div>
    @endif

    <div class="w2k-help-nav">
        @foreach($topics as $key => $meta)
            @continue($focus && $focus !== $key)
            @php
                $href = $focus
                    ? $helpIndexUrl.'?topic='.urlencode($key)
                    : '#'.$key;
            @endphp
            <a href="{{ $href }}" class="{{ $focus === $key ? 'is-active' : '' }}" data-topic="{{ $key }}">{{ $meta['nav'] }}</a>
        @endforeach
    </div>

    @if($show('start'))
    <div class="w2k-help-card" id="start">
        <h3>Sign in</h3>
        <p>Open <a href="{{ url('/login') }}" target="_blank" rel="noopener">{{ parse_url(url('/'), PHP_URL_HOST) }}/login</a>. Use your staff email, username, or WhatsApp number. After sign-in you can switch workspace if you are both a member and staff.</p>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/login.png') }}?v={{ $hv }}" alt="Staff sign-in card">
        <p class="w2k-help-cap">Login card: club logo, Email or Username, Password, Forgot username or password, Login with WhatsApp OTP, and Sign in. Version ({{ $hv }}) appears at the bottom of the card.</p>
        <ol>
            <li>Click <strong>Login</strong> in the public header.</li>
            <li>Enter email / WhatsApp number / username and password, then Sign in.</li>
            <li>Or tap <strong>Login with WhatsApp OTP</strong> if you prefer a code.</li>
            <li>You land on the admin dashboard. The gold <strong>POS</strong> button is in the top bar if you sell.</li>
        </ol>
    </div>
    @endif

    @if($show('find-help'))
    <div class="w2k-help-card" id="find-help">
        <h3>Where to find Help</h3>
        <p>Help is always one click away. Use the last horizontal tab on any module you can see, the Help menu at the bottom of the sidebar, or the Help icon in the top bar next to POS.</p>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/tabs-help.png') }}?v={{ $hv }}" alt="Module tabs ending with Help">
        <p class="w2k-help-cap">Every module’s tab row ends with <strong>Help</strong>. That link opens only this module’s help (permission-checked).</p>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/sidebar-help.png') }}?v={{ $hv }}" alt="Sidebar with Help as the last menu item">
        <p class="w2k-help-cap">Scroll the black sidebar to the bottom. <strong>Help</strong> sits last, under Settings, just above your profile.</p>
        <ol>
            <li>Work in any module you have permission for.</li>
            <li>Click the last tab, labelled Help — you see that menu’s guide only.</li>
            <li>Or open Help in the sidebar for the full guide filtered to your permissions.</li>
        </ol>
    </div>
    @endif

    @if($show('website'))
    <div class="w2k-help-card" id="website">
        <h3>Public website</h3>
        <p>Guests see Home, Menu, Events, About, Reviews, and Become a Member in the header (with EN / FR / RW). Edit page words in <strong>Site Content</strong> — not in Products.</p>
        <h4>Home</h4>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/home.png') }}?v={{ $hv }}" alt="Home page hero">
        <p class="w2k-help-cap">Home fills the screen with the current Welcome 2 Kigali artwork. Gold CTAs and the language switcher sit in the header. Version is shown on the public site.</p>
        <h4>Cafe menu (what guests order)</h4>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/menu.png') }}?v={{ $hv }}" alt="Cafe and Restaurant menu">
        <p class="w2k-help-cap">Menu uses parent categories and sub-categories with images. Guests pick Dine In or Take away (+1,000 FRW packing). Cart checkout creates an online cafe order.</p>
        <h4>About the club</h4>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/about.png') }}?v={{ $hv }}" alt="About page">
        <p class="w2k-help-cap">About covers vision, mission, and leadership. Footer lists restaurant &amp; cafe, lounge, and contact details.</p>
        <h4>Events</h4>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/events.png') }}?v={{ $hv }}" alt="Club Events page">
        <p class="w2k-help-cap">Club Events: search and filters. Add events under Admin → Events and they appear here automatically.</p>
        <h4>Become a Member</h4>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/register.png') }}?v={{ $hv }}" alt="Become a Member plans">
        <p class="w2k-help-cap">Become a Member (<a href="{{ url('/membership/apply') }}" target="_blank" rel="noopener">/membership/apply</a>) → choose a plan, then continue with ID / passport scan when required.</p>
    </div>
    @endif

    @if($show('reviews'))
    <div class="w2k-help-card" id="reviews">
        <h3>Guest reviews</h3>
        <p>Public reviews live on the <strong>Reviews</strong> tab (not on Home). Guests rate and comment there. Signature, country, and OTP phone are collected for verification on the backend; staff moderate under Site Content → Reviews when they have permission.</p>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/reviews.png') }}?v={{ $hv }}" alt="Public Reviews page">
        <p class="w2k-help-cap">Reviews page: guests leave ratings and comments here only. Home no longer embeds the review form.</p>
        <ol>
            <li>Guest opens Reviews from the public header.</li>
            <li>They leave a rating and comment; verification fields are handled securely.</li>
            <li>Approved reviews show on the Reviews page only.</li>
        </ol>
    </div>
    @endif

    @if($show('services'))
    <div class="w2k-help-card" id="services">
        <h3>Services catalog</h3>
        <p>Public services use parent categories with sub-categories and menu images. Manage structure under Site Content / service catalog so the Menu page matches live offerings (coffee, food, laundry items, etc.).</p>
        <ol>
            <li>Set parent categories, then nest sub-categories under them.</li>
            <li>Attach images so guests see the current look on Menu.</li>
            <li>Laundry and cafe items stay in their own flows (POS / Orders / Laundry boards).</li>
        </ol>
    </div>
    @endif

    @if($show('products'))
    <div class="w2k-help-card" id="products">
        <h3>Products — cafe &amp; retail items</h3>
        <p>Open <strong>Product</strong> in the sidebar. Tabs include Category, Product List, Add Product, and Help.</p>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/products-list.png') }}?v={{ $hv }}" alt="Product List">
        <p class="w2k-help-cap">Product List: search, add/import, and inline edit for name, quantity, price, cost, brand, category, and unit.</p>
        <ol>
            <li>Search by name or product code.</li>
            <li>Click a price or quantity cell to update stock or FRW price; press Enter or click away to save.</li>
            <li>Use Action → Edit for image, tax, or membership benefit.</li>
        </ol>
        <h4>Add a product</h4>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/add-product.png') }}?v={{ $hv }}" alt="Add Product form">
        <p class="w2k-help-cap">Add Product: type, name, code, barcode, brand, category, units, cost, price, quantity, and optional image.</p>
        <ol>
            <li>Product → Add Product.</li>
            <li>Name the item, pick category and unit, set cost and price.</li>
            <li>Save — it appears on Product List and on the public Menu when active.</li>
        </ol>
    </div>
    @endif

    @if($show('catalog'))
    <div class="w2k-help-card" id="catalog">
        <h3>Live cafe catalog</h3>
        <p>Active products in the database (prices in FRW). This table updates when you add or rename items.</p>
        @forelse($grouped as $category => $items)
            <h4>{{ $category }} ({{ $items->count() }})</h4>
            <div class="table-responsive">
                <table class="w2k-help-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Price</th>
                            <th>Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $p)
                            <tr>
                                <td>{{ $p->name }}</td>
                                <td>{{ $p->code }}</td>
                                <td>{{ number_format((float) $p->price) }} FRW</td>
                                <td>{{ $p->unit ? $p->unit->unit_name : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <p class="w2k-help-cap">No active products to list, or you opened Help focused on another topic.</p>
        @endforelse
        @if($products->count())
            <p class="w2k-help-cap mt-2">{{ $products->count() }} active products.</p>
        @endif
    </div>
    @endif

    @if($show('pos'))
    <div class="w2k-help-card" id="pos">
        <h3>POS and cafe orders</h3>
        <p>Click the gold <strong>POS</strong> button in the top bar (next to Help).</p>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/pos.jpg') }}?v={{ $hv }}" alt="POS ticket and payment buttons">
        <p class="w2k-help-cap">POS: customer on the left, product search and ticket in the middle, category grid on the right. Payment bar includes Send to Laundry, Cash, MoMo, VISA, Deposit, Credit, Group (reorder under Site Content → POS Payments). One <strong>Comment</strong> field per line follows the item. Grand Total stays visible while you scroll.</p>
        <ol>
            <li>Choose Walk-in Customer or pick a member. Their phone is used for MoMo and WhatsApp.</li>
            <li>Add lines by search or Category / Brand / Featured. Adjust qty; price and description stay readable beside qty.</li>
            <li>Set Service to <strong>Dine In</strong> or <strong>Take Away</strong> (+1,000 FRW packing for take away), table, and waitress when serving cafe food/drinks.</li>
            <li>Pay with <strong>Cash</strong>, <strong>VISA</strong>, <strong>Credit</strong>, <strong>Deposit</strong>, or <strong>MoMo</strong> (MTN 078/079 or Airtel 072/073 from the number).</li>
            <li>Laundry dresses: comment + <strong>Send to Laundry</strong> → Laundry boards. Cafe food/coffee/bar lines go to <strong>Orders</strong> lanes. Fashion and other goods stay on the sale only.</li>
        </ol>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/pos-momo.png') }}?v={{ $hv }}" alt="Finalize Sale MoMo">
        <p class="w2k-help-cap">Finalize Sale → MoMo: phone auto-fills, partial Paying Amount allowed. Sale is paid only after PawaPay confirms — then invoice print and WhatsApp.</p>
        <div class="w2k-help-tip">If Add Cash Register opens first, enter 0 (or drawer cash) and submit. Do not close the MoMo wait screen until it shows paid.</div>
    </div>
    @endif

    @if($show('content'))
    <div class="w2k-help-card" id="content">
        <h3>Site Content</h3>
        <p>Use <strong>Site Content</strong> for public pages and menu order. It does not change products, members, or sales. Grant it under <strong>Settings → Role Permission → Site Content</strong>.</p>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/site-content.png') }}?v={{ $hv }}" alt="Site Content tabs">
        <p class="w2k-help-cap">Pages (Home, About, Menu, Reviews, Events, Become a Member, Footer, …) and Menus &amp; Order (Landing Menu, Side Bars, POS Payments, …).</p>
        <ul>
            <li><strong>Pages</strong> — Home buttons, About, Events title, Menu title, Reviews, Become a Member, Footer.</li>
            <li><strong>Landing Menu</strong> — show, hide, rename, reorder public header links.</li>
            <li><strong>Side Bars</strong> — admin sidebar order, including Membership, Laundry, and Orders.</li>
            <li><strong>POS Payments</strong> — button order and labels (Send to Laundry, Cash, MoMo, VISA, …).</li>
        </ul>
    </div>
    @endif

    @if($show('members'))
    <div class="w2k-help-card" id="members">
        <h3>Membership</h3>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/membership.png') }}?v={{ $hv }}" alt="Membership dashboard">
        <p class="w2k-help-cap">Membership tabs end with Help. Dashboard cards cover active, expiring, applications, and revenue.</p>
        <ol>
            <li>Guests apply on Become a Member.</li>
            <li>Staff review under Membership → Applications.</li>
            <li>Approved members can sign in and use POS benefits when enabled on a product.</li>
        </ol>
    </div>
    @endif

    @if($show('laundry'))
    <div class="w2k-help-card" id="laundry">
        <h3>Laundry</h3>
        <p>Dresses sold on POS (Laundry category) open a ticket. Flow: <strong>Awaiting Acceptance</strong> → <strong>Awaiting cleaning</strong> → <strong>Ready for collection</strong> → <strong>Collect</strong> (signature). WhatsApp updates the client at each step. Sidebar badges show pending counts; the order pop alerts you to new work.</p>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/laundry.png') }}?v={{ $hv }}" alt="Laundry Ready for collection">
        <p class="w2k-help-cap">Ready for collection: one row per customer, dress count, collection time, Paid / Due. Open a name to take remaining payment, then Collect with signature.</p>
        <ol>
            <li>On POS, add a laundry dress, write the shared <strong>Comment</strong>, then <strong>Send to Laundry</strong> or take payment.</li>
            <li><strong>Awaiting Acceptance</strong> — open the customer, tick dresses, set collection date/time, Accept. Unaccepted dresses stay here.</li>
            <li><strong>Awaiting cleaning</strong> — tick cleaned dresses → Mark selected cleaned (staff name stamped).</li>
            <li><strong>Ready for collection</strong> — take remaining payment if needed, then Collect. Signed tickets leave this board.</li>
            <li>Use <strong>On Hold</strong> with a comment to park work; <strong>Resume</strong> returns to the previous stage.</li>
        </ol>
        <div class="w2k-help-tip">Clothes cannot be collected until the bill is paid. All tickets start from POS. MoMo follows the same PawaPay confirm-then-paid rule.</div>
    </div>
    @endif

    @if($show('restaurant'))
    <div class="w2k-help-card" id="restaurant">
        <h3>Orders · Restaurant</h3>
        <p>Food for the restaurant lane. Open <strong>Orders → Restaurant</strong>. Tabs: Awaiting, Accepted, Ready for Delivery, On Hold, Delivered. Pending counts pop and badge so you do not miss tickets.</p>
        <ol>
            <li>On POS, set <strong>Dine In</strong> or <strong>Take Away</strong> (+1,000 FRW packing), table, waitress, and one <strong>Comment</strong> per line.</li>
            <li>Accept with optional minutes, or <strong>Put on hold</strong> with a comment, then Resume. Comment updates stay on the ticket and tracking page.</li>
            <li>Mark Ready for Delivery, then Delivered. The guest is messaged at each step.</li>
        </ol>
        <p>Kitchen accepts Restaurant and Coffee &amp; Tea. Coffee and Bar have their own Help tabs when you open those menus.</p>
    </div>
    @endif

    @if($show('restaurant-coffee'))
    <div class="w2k-help-card" id="restaurant-coffee">
        <h3>Orders · Coffee &amp; Tea</h3>
        <p>Cafe drinks land under <strong>Orders → Coffee &amp; Tea</strong>.</p>
        <ol>
            <li>Watch the pop-up or the Coffee &amp; Tea badge for pending counts, then open Awaiting.</li>
            <li>Accept with minutes, put on hold, or add a comment — the same note shows on ticket and tracking.</li>
            <li>Ready for Delivery when the drink is done, then Delivered when it reaches the guest.</li>
        </ol>
    </div>
    @endif

    @if($show('restaurant-bar'))
    <div class="w2k-help-card" id="restaurant-bar">
        <h3>Orders · Bar</h3>
        <p>Bar / drinks lane under <strong>Orders → Bar</strong>.</p>
        <ol>
            <li>Open Awaiting from the Bar badge or the order pop-up.</li>
            <li>Accept, hold, or comment the same way as Restaurant / Coffee.</li>
            <li>Ready for Delivery → Delivered when the drink is handed over.</li>
        </ol>
    </div>
    @endif

    @if($show('orders'))
    <div class="w2k-help-card" id="orders">
        <h3>Online orders</h3>
        <p>Guest cafe orders from <a href="{{ url('/menu') }}" target="_blank" rel="noopener">/menu</a> land under <strong>Orders → Online orders</strong>.</p>
        <ol>
            <li>Guest picks Dine In or Take away, adds items from parent/sub categories, and checks out.</li>
            <li>Staff open Online orders to see items, payment, and status.</li>
            <li>POS walk-in sales stay under Sale → Sale List; website carts stay here.</li>
        </ol>
    </div>
    @endif

    @if($show('rental'))
    <div class="w2k-help-card" id="rental">
        <h3>Rental module</h3>
        <p>Use <strong>Rental Module</strong> for equipment, accommodation, studio, or software bookings.</p>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/rental.png') }}?v={{ $hv }}" alt="Create Equipment Rental Booking">
        <p class="w2k-help-cap">Create booking: Customer, Warehouse, Biller, CC, rental period, equipment search, and order table. Tabs end with Help.</p>
        <ol>
            <li>Rental Module → Booking Create.</li>
            <li>Pick customer (phone required for signature WhatsApp).</li>
            <li>Set From / To dates, add products, optionally send for signature.</li>
            <li>After the client signs, countersign under Pending Review / Signed Contracts.</li>
        </ol>
    </div>
    @endif

    @if($show('quotations'))
    <div class="w2k-help-card" id="quotations">
        <h3>Quotations</h3>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/quotation.png') }}?v={{ $hv }}" alt="Quotation List">
        <p class="w2k-help-cap">Quotation List with status chips (Awaiting Signature, Client Quotes, Approval / Final, Rejected, Drafts) and Help last.</p>
        <ol>
            <li>Quotation → Add Quotation. Choose customer and lines.</li>
            <li>Send via WhatsApp. Client Sign &amp; Approve, Reject, or Quote.</li>
            <li>Official PDF goes out only after they sign. Quote the message serial (e.g. W2K/MSG/…) if you follow up.</li>
        </ol>
    </div>
    @endif

    @if($show('contracts'))
    <div class="w2k-help-card" id="contracts">
        <h3>Contracts</h3>
        <p>Contracts are sent from Rental tabs or from Contracts in the sidebar.</p>
        <ol>
            <li>Create the booking or Contracts → Create Contract.</li>
            <li>Add signatories with phone numbers and issue the request.</li>
            <li>Client opens the WhatsApp link, reads, signs, and submits.</li>
            <li>Track under Awaiting Signature, Pending Review, and Signed Contracts.</li>
        </ol>
        <div class="w2k-help-tip">Clients do not need an account — they use the WhatsApp link.</div>
    </div>
    @endif

    @if($show('expenses'))
    <div class="w2k-help-card" id="expenses">
        <h3>Expenses</h3>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/expenses.png') }}?v={{ $hv }}" alt="Expense List">
        <p class="w2k-help-cap">Expense Category, Expense List, Help. Date range filter and expense table.</p>
        <ol>
            <li>Expense → Expense List (or Expense Category first).</li>
            <li>Add category, warehouse, account, amount, and note.</li>
            <li>Submit — it appears on the list immediately.</li>
        </ol>
    </div>
    @endif

    @if($show('returns'))
    <div class="w2k-help-card" id="returns">
        <h3>Returns</h3>
        <ol>
            <li>Return → Sale (or Purchase).</li>
            <li>Select customer, warehouse, and products coming back.</li>
            <li>Submit. Stock increases again.</li>
        </ol>
    </div>
    @endif

    @if($show('assets'))
    <div class="w2k-help-card" id="assets">
        <h3>Fixed assets</h3>
        <ol>
            <li>Fixed Assets → Assets Dashboard or Assets List.</li>
            <li>Add Assets for new items. Use Activity / Repair / Expense for running costs.</li>
            <li>Reports sit at the bottom of the Fixed Assets menu.</li>
        </ol>
    </div>
    @endif

    @if($show('events'))
    <div class="w2k-help-card" id="events">
        <h3>Events</h3>
        <p>Admin → Events. New events appear on the public Club Events page automatically.</p>
        <p>If the public page shows No Events Found, add an event in admin or change the Upcoming filter.</p>
    </div>
    @endif

    @if($show('purchase'))
    <div class="w2k-help-card" id="purchase">
        <h3>Purchases</h3>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/purchase.png') }}?v={{ $hv }}" alt="Purchase List">
        <p class="w2k-help-cap">Purchase List, Add Purchase, Import Purchase By CSV, Help.</p>
        <ol>
            <li>Purchase → Add Purchase or Import by CSV.</li>
            <li>Supplier, warehouse, products, cost, and payment.</li>
            <li>Stock increases when the purchase is recorded.</li>
        </ol>
    </div>
    @endif

    @if($show('payments'))
    <div class="w2k-help-card" id="payments">
        <h3>Awaiting Payment</h3>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/payments.png') }}?v={{ $hv }}" alt="Awaiting Payment list">
        <p class="w2k-help-cap">Due balances with Add Payment per row (Cash / MoMo / VISA).</p>
        <ol>
            <li>Open Sale → Awaiting Payment (or Payments).</li>
            <li>Find the customer and remaining Due.</li>
            <li>Add Payment and save. MoMo detects MTN or Airtel from the phone number.</li>
        </ol>
    </div>
    @endif

    @if($show('people'))
    <div class="w2k-help-card" id="people">
        <h3>People</h3>
        <ul>
            <li><strong>Customers</strong> — sales, rentals, quotations. Phone required for WhatsApp.</li>
            <li><strong>Users</strong> — staff logins and roles.</li>
            <li><strong>Billers / Suppliers</strong> — invoices and purchases.</li>
        </ul>
    </div>
    @endif

    @if($show('accounting'))
    <div class="w2k-help-card" id="accounting">
        <h3>Accounting</h3>
        <p>Accounts, money transfers, and statements. Set at least one default account so sales, returns, and expenses can post.</p>
    </div>
    @endif

    @if($show('letters'))
    <div class="w2k-help-card" id="letters">
        <h3>Letters</h3>
        <p>Letters → compose, approve, and send official letters with the club letterhead.</p>
    </div>
    @endif

    @if($show('invitations'))
    <div class="w2k-help-card" id="invitations">
        <h3>Digital invitations</h3>
        <p>Digital Invitations → create an event, add guests, send WhatsApp/email invites with RSVP links.</p>
    </div>
    @endif

    @if($show('internships'))
    <div class="w2k-help-card" id="internships">
        <h3>Internships</h3>
        <p>Internships hub for programmes, enrolment, supervisor grading, and timesheets.</p>
    </div>
    @endif

    @if($show('tasks'))
    <div class="w2k-help-card" id="tasks">
        <h3>Tasks, jobs, announcements, courses, timesheets</h3>
        <ul>
            <li><strong>Task Manager</strong> — assign and track internal work.</li>
            <li><strong>Job Board</strong> — public vacancies at <a href="{{ url('/apply-now') }}" target="_blank" rel="noopener">/apply-now</a>.</li>
            <li><strong>Announcements</strong> — bulk WhatsApp messages.</li>
            <li><strong>Courses</strong> — course list, registrations, certificates.</li>
            <li><strong>TimeSheets</strong> — employees fill weeks; TimeSheet Admin reviews overtime.</li>
        </ul>
    </div>
    @endif

    @if($show('reports'))
    <div class="w2k-help-card" id="reports">
        <h3>Reports</h3>
        <p>Reports in the sidebar cover sales, purchases, and stock. Fixed Assets has its own report tabs.</p>
    </div>
    @endif

    @if($show('settings'))
    <div class="w2k-help-card" id="settings">
        <h3>Settings</h3>
        <p>Settings hub tabs (General, Warehouse, Units, Tax, POS, Mail, …) end with <strong>Help</strong>. Use General Setting for company name, logo, and currency (RWF). Role Permission controls who sees each module’s Help.</p>
    </div>
    @endif

    @if($show('transfer'))
    <div class="w2k-help-card" id="transfer">
        <h3>Stock transfer</h3>
        <p>Transfer → Add Transfer to move stock between warehouses.</p>
    </div>
    @endif

    @if($show('whatsapp'))
    <div class="w2k-help-card" id="whatsapp">
        <h3>WhatsApp links</h3>
        <p>Clients should land on the document or sign page — never the homepage.</p>
        <ol>
            <li>Send the quotation or contract from admin.</li>
            <li>The message has the URL on its own line (Review quotation / Open / Sign).</li>
            <li>If an old message still opens Home, <strong>resend</strong> it — do not reuse an old green link.</li>
        </ol>
    </div>
    @endif
</div>

<script>
    $("ul#help-module").siblings('a').attr('aria-expanded','true');
    $("ul#help-module").addClass("show");
    $("ul#help-module #help-guide-menu").addClass("active");
    (function () {
        var focus = @json($focus);
        function markNav() {
            if (focus) {
                $('.w2k-help-nav a[data-topic="' + focus + '"]').addClass('is-active');
                return;
            }
            var hash = (window.location.hash || '').replace('#','');
            $('.w2k-help-nav a').removeClass('is-active');
            var $a = hash ? $('.w2k-help-nav a[data-topic="' + hash + '"]') : $();
            if (!$a.length) $a = $('.w2k-help-nav a').first();
            $a.addClass('is-active');
        }
        markNav();
        $(window).on('hashchange', markNav);
        $('.w2k-help-nav a').on('click', function () {
            if (focus) return;
            $('.w2k-help-nav a').removeClass('is-active');
            $(this).addClass('is-active');
        });
    })();
</script>
@endsection
