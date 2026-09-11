@extends('layout.main')

@section('content')
<style>
    .w2k-help { max-width: 1100px; }
    .w2k-help-hero {
        background: linear-gradient(135deg, #1a1a1a 0%, #3a2c16 60%, #c5a059 140%);
        color: #f7f1e8;
        border-radius: 18px;
        padding: 28px 28px 22px;
        margin-bottom: 20px;
    }
    .w2k-help-hero h2 { margin: 0 0 6px; font-size: 28px; }
    .w2k-help-hero p { margin: 0; color: #e8d7b0; }
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
</style>

<div class="container-fluid w2k-help">
    <div class="w2k-help-hero">
        <span class="w2k-help-kicker">Welcome 2 Kigali</span>
        <h2>Staff user guide</h2>
        <p>How to run the cafe, website, and membership using the live catalog — {{ $products->count() }} products such as Espresso (W2K-001, 2,000 FRW) and African Coffee (W2K-010, 4,000 FRW).</p>
    </div>

    <div class="w2k-help-nav">
        <a href="#start" class="is-active">Start here</a>
        <a href="#website">Public website</a>
        <a href="#products">Products</a>
        <a href="#catalog">Live cafe catalog</a>
        <a href="#pos">POS &amp; sales</a>
        <a href="#content">Site Content</a>
        <a href="#members">Membership</a>
    </div>

    <div class="w2k-help-card" id="start">
        <h3>1. Sign in</h3>
        <p>Open <a href="{{ url('/login') }}" target="_blank" rel="noopener">welcome2kigali.net/login</a>. Use your staff email, username, or WhatsApp number. After sign-in you can switch workspace if you are both a member and staff.</p>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/login.png') }}" alt="Welcome 2 Kigali sign-in page">
        <p class="w2k-help-cap">The live login screen. Choose Sign in, or WhatsApp OTP if you prefer a code.</p>
        <ol>
            <li>Go to Login in the public header.</li>
            <li>Enter email / WhatsApp / username and password.</li>
            <li>You land on the admin dashboard. POS is in the top bar if you sell.</li>
        </ol>
    </div>

    <div class="w2k-help-card" id="website">
        <h3>2. Public website</h3>
        <p>Guests see Home, About, Events, Menu, and Become a Member. Those pages are edited in <strong>Site Content</strong> — not in Products.</p>
        <h4>Home</h4>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/home.png') }}" alt="Welcome 2 Kigali home page">
        <p class="w2k-help-cap">Home: Join the Club, Events, and Cafe Menu buttons over the landing artwork.</p>
        <h4>Cafe menu (what guests order)</h4>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/menu.png') }}" alt="Cafe menu showing Espresso, Americano, Cappuccino">
        <p class="w2k-help-cap">Live menu: Espresso 2,000 FRW, Americano 2,000 FRW, Flat White 2,500 FRW, Cappuccino 3,000 FRW, Café Latte 3,000 FRW, African Coffee 4,000 FRW.</p>
        <h4>About the club</h4>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/about.png') }}" alt="About page vision and mission">
        <p class="w2k-help-cap">About shows vision, mission, leaders, and contact. Change the wording in Site Content → About.</p>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/events.png') }}" alt="Events page">
        <p class="w2k-help-cap">Events lists club gatherings. Add events under Admin → Events — they appear here automatically.</p>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/register.png') }}" alt="Become a Member page">
        <p class="w2k-help-cap">Become a Member is the public application form.</p>
    </div>

    <div class="w2k-help-card" id="products">
        <h3>3. Products — cafe items</h3>
        <p>Open <strong>Product → Product List</strong>. This is the same catalog guests see on /menu. Example rows already on the system: African Coffee W2K-010, Americano W2K-002, Avocado Smoothie W2K-048.</p>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/products-list.png') }}" alt="Product list with African Coffee, African Tea, Americano">
        <p class="w2k-help-cap">Click a name, quantity, price, cost, brand, category, or unit to change it. Press Enter or click away to save. Image and Action still open the usual menus.</p>
        <ol>
            <li>Search for <em>Espresso</em> or code <em>W2K-001</em>.</li>
            <li>Click the price cell if a drink changes (e.g. Cappuccino 3,000 FRW).</li>
            <li>Click quantity to set stock. That number is what POS and the list show.</li>
            <li>Use Action → Edit only when you need image, tax, or membership benefit.</li>
        </ol>
        <h4>Add a product</h4>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/add-product.png') }}" alt="Add Product form">
        <p class="w2k-help-cap">Add Product. Code is generated (W2K-…). Image is optional. Default category is Food; cafe drinks should use Coffee &amp; Tea, Iced &amp; Specialty, Tea &amp; Hot Beverages, Fresh &amp; Detox Juices, or Smoothies.</p>
        <ol>
            <li>Product → Add Product.</li>
            <li>Name: e.g. <em>Espresso</em>. Leave the code or press refresh.</li>
            <li>Category: Coffee &amp; Tea. Unit: Portion. Price: 2000. Cost: 2000.</li>
            <li>Paste or drop a photo if you have one. Save product.</li>
            <li>It appears on Product List and on the public Menu.</li>
        </ol>
    </div>

    <div class="w2k-help-card" id="catalog">
        <h3>4. Live cafe catalog</h3>
        <p>These are the products currently in the Welcome 2 Kigali database (prices in FRW, tax included on the public menu).</p>
        @foreach($grouped as $category => $items)
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
        @endforeach
        <p class="w2k-help-cap mt-2">{{ $products->count() }} active products. This table updates when you add or rename items.</p>
    </div>

    <div class="w2k-help-card" id="pos">
        <h3>5. POS and cafe orders</h3>
        <ol>
            <li>Click <strong>POS</strong> in the top bar (shopping bag icon).</li>
            <li>Search <em>Cappuccino</em> or tap Coffee &amp; Tea. Add Espresso, Flat White, or Ice Mocha the same way.</li>
            <li>Choose the customer or walk-in. Take cash, MoMo, or card.</li>
            <li>Online cafe orders also appear under Sale / Online Order when a guest adds from /menu.</li>
        </ol>
        <p>Example ticket: Espresso 2,000 + Cappuccino 3,000 + Avocado Smoothie 5,000 = 10,000 FRW.</p>
    </div>

    <div class="w2k-help-card" id="content">
        <h3>6. Site Content (website words and menus)</h3>
        <p>Use <strong>Site Content</strong> for pages and menus. It does not change products, members, or sales.</p>
        <ul>
            <li><strong>Pages</strong> — Home buttons, About vision/mission, Events title, Menu title, Become a Member, Footer.</li>
            <li><strong>Landing Menu</strong> — show, hide, rename, reorder Home / About / Events / Menu / Become a Member.</li>
            <li><strong>Side Bars</strong> — same for the admin sidebar, including About Us Leaders, Digital Invitations, and Internships.</li>
            <li><strong>Admin modules</strong> — shortcuts to Leaders, Invitations, and Internships.</li>
        </ul>
        <p>Press Save on a tab. Then open <a href="{{ url('/') }}" target="_blank" rel="noopener">the public site</a> to check.</p>
    </div>

    <div class="w2k-help-card" id="members">
        <h3>7. Membership</h3>
        <ol>
            <li>Guests apply on Become a Member.</li>
            <li>Staff review applications under <strong>Membership</strong>.</li>
            <li>Approved members can sign in and use POS benefits if you enabled them on a product (e.g. a free tea).</li>
        </ol>
        <p>Settings (units, brands, tax, warehouse) stay under the <strong>Settings</strong> hub — not Site Content.</p>
    </div>
</div>

<script>
    $("ul#help-module").siblings('a').attr('aria-expanded','true');
    $("ul#help-module").addClass("show");
    $("ul#help-module #help-guide-menu").addClass("active");
</script>
@endsection
