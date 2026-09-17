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
    .w2k-help-tip {
        background: #fff8ea;
        border-left: 4px solid #c5a059;
        padding: 10px 14px;
        border-radius: 0 10px 10px 0;
        margin: 12px 0;
        font-size: 14px;
    }
</style>

<div class="container-fluid w2k-help">
    <div class="w2k-help-hero">
        <span class="w2k-help-kicker">Welcome 2 Kigali</span>
        <h2>Staff user guide</h2>
        <p>How to run Welcome 2 Kigali — cafe, website, rentals, quotations, contracts, membership, and accounts. Open Help from the last tab on any module, or from the bottom of the sidebar.</p>
    </div>

    <div class="w2k-help-nav">
        <a href="#start">Start</a>
        <a href="#find-help">Find Help</a>
        <a href="#website">Website</a>
        <a href="#products">Products</a>
        <a href="#catalog">Catalog</a>
        <a href="#pos">POS</a>
        <a href="#rental">Rental</a>
        <a href="#quotations">Quotations</a>
        <a href="#contracts">Contracts</a>
        <a href="#members">Membership</a>
        <a href="#expenses">Expenses</a>
        <a href="#returns">Returns</a>
        <a href="#orders">Online orders</a>
        <a href="#assets">Assets</a>
        <a href="#events">Events</a>
        <a href="#purchase">Purchase</a>
        <a href="#people">People</a>
        <a href="#content">Site Content</a>
        <a href="#accounting">Accounting</a>
        <a href="#letters">Letters</a>
        <a href="#invitations">Invitations</a>
        <a href="#internships">Internships</a>
        <a href="#tasks">Tasks &amp; jobs</a>
        <a href="#reports">Reports</a>
        <a href="#settings">Settings</a>
        <a href="#transfer">Transfer</a>
        <a href="#whatsapp">WhatsApp links</a>
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

    <div class="w2k-help-card" id="find-help">
        <h3>Where to find Help</h3>
        <p>Help is always one click away. Use the last horizontal tab on any module, the Help menu at the bottom of the sidebar, or the Help dropdown in the top bar.</p>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/tabs-help.png') }}" alt="Module tabs with Help as the last tab">
        <p class="w2k-help-cap">Every module row ends with a gold Help tab. It opens this guide at the matching section (Rental, Products, Expenses, …).</p>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/sidebar-help.png') }}" alt="Sidebar with Help at the bottom">
        <p class="w2k-help-cap">The Help menu sits at the bottom of the sidebar, under Settings. Open User Guide or jump to a topic.</p>
        <ol>
            <li>Work in any module (Product, Rental, Sale, …).</li>
            <li>Click the last tab, labelled Help.</li>
            <li>Or scroll the sidebar to Help, or use the question-mark Help in the header.</li>
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
        <img class="w2k-help-shot" src="{{ url('public/branding/help/pos.png') }}" alt="POS ticket with Espresso, Cappuccino, Avocado Smoothie">
        <p class="w2k-help-cap">POS: pick a category, tap drinks, take cash or MoMo. Help is in the POS header.</p>
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

    <div class="w2k-help-card" id="rental">
        <h3>8. Rental module</h3>
        <p>Use <strong>Rental Module</strong> for equipment, accommodation, studio, or software bookings.</p>
        <ol>
            <li>Open Rental Module → Add Booking (or the first tab).</li>
            <li>Pick the customer. Their phone is required if you send a signature link.</li>
            <li>Add products/lines with start and end dates.</li>
            <li>To send the agreement on WhatsApp, tick send for signature and choose the contract type (equipment, accommodation, licenses, studio).</li>
            <li>Submit. The client gets a WhatsApp link. After they sign, staff countersign and a receipt is sent.</li>
        </ol>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/rental.png') }}" alt="Rental module tabs with Help last">
        <p class="w2k-help-cap">Rental tabs: Add Booking, Booking List, Requests, then Help at the end.</p>
        <div class="w2k-help-tip">If Submit showed a server error before, that is fixed. If a WhatsApp link opened Home, resend the booking agreement so the client gets a clean URL.</div>
    </div>

    <div class="w2k-help-card" id="quotations">
        <h3>9. Quotations</h3>
        <ol>
            <li>Quotation → Add Quotation. Choose the customer and add lines.</li>
            <li>Send via WhatsApp. The client opens <em>Review quotation</em>, then Sign &amp; Approve, Reject, or Quote.</li>
            <li>The official PDF goes out only after they sign.</li>
        </ol>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/quotation.png') }}" alt="Quotation list and WhatsApp review link">
        <p class="w2k-help-cap">Send the quotation, then the client taps Review quotation on WhatsApp. Resend old messages after the link fix.</p>
        <div class="w2k-help-tip">Always resend the quotation after this update if a client still has an old WhatsApp message — old links could open the homepage.</div>
    </div>

    <div class="w2k-help-card" id="contracts">
        <h3>10. Contracts</h3>
        <ol>
            <li>Contracts → Create Contract (or send from Rental).</li>
            <li>Add signatories with phone numbers. Issue the request.</li>
            <li>The client opens the green WhatsApp link, reads the contract, draws a signature, and submits.</li>
            <li>Track status under Awaiting Client / Awaiting Admin / Signed.</li>
        </ol>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/login.png') }}" alt="Staff sign-in used before sending contracts">
        <p class="w2k-help-cap">Staff sign in first, then send the contract. Clients do not need an account — they use the WhatsApp link.</p>
    </div>

    <div class="w2k-help-card" id="expenses">
        <h3>11. Expenses</h3>
        <ol>
            <li>Expense → Expense List.</li>
            <li>Choose expense category, product category if shown, warehouse, account, amount, and note.</li>
            <li>Submit. It appears on the list immediately.</li>
        </ol>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/expenses.png') }}" alt="Add Expense form with Help as last tab">
        <p class="w2k-help-cap">Expense → Add Expense. Category, warehouse, account, amount, note. Help is the last tab.</p>
    </div>

    <div class="w2k-help-card" id="returns">
        <h3>12. Returns</h3>
        <ol>
            <li>Return → Sale (or Purchase).</li>
            <li>Select customer, warehouse, and the products coming back.</li>
            <li>Submit. Stock is increased again.</li>
        </ol>
    </div>

    <div class="w2k-help-card" id="orders">
        <h3>13. Online orders</h3>
        <p>Guest cafe orders from <a href="{{ url('/menu') }}" target="_blank" rel="noopener">/menu</a> land under <strong>Online Order</strong>.</p>
        <ol>
            <li>Open Online Order → Online Order List.</li>
            <li>Open a row to see items, payment, and status.</li>
            <li>POS sales stay under Sale → Sale List. Online guest tickets stay here.</li>
        </ol>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/menu.png') }}" alt="Public cafe menu where guests place online orders">
        <p class="w2k-help-cap">Guests add drinks from this menu. Paid orders show in Online Order.</p>
    </div>

    <div class="w2k-help-card" id="assets">
        <h3>14. Fixed assets</h3>
        <ol>
            <li>Fixed Assets → Assets Dashboard or Assets List.</li>
            <li>Add Assets for new items. Use Asset Activity / Repair / Expense for running costs.</li>
            <li>Reports sit at the bottom of the Fixed Assets menu.</li>
        </ol>
    </div>

    <div class="w2k-help-card" id="events">
        <h3>15. Events</h3>
        <p>Admin → Events. New events appear on the public Events page automatically.</p>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/events.png') }}" alt="Public events page">
        <p class="w2k-help-cap">What guests see after you publish an event.</p>
    </div>

    <div class="w2k-help-card" id="purchase">
        <h3>16. Purchases</h3>
        <ol>
            <li>Purchase → Add Purchase.</li>
            <li>Supplier, warehouse, products, cost, and payment.</li>
            <li>Stock increases when the purchase is recorded.</li>
        </ol>
    </div>

    <div class="w2k-help-card" id="people">
        <h3>17. People</h3>
        <ul>
            <li><strong>Customers</strong> — needed for sales, rentals, quotations. Phone number is required for WhatsApp.</li>
            <li><strong>Users</strong> — staff logins and roles.</li>
            <li><strong>Billers / Suppliers</strong> — for invoices and purchases.</li>
        </ul>
    </div>

    <div class="w2k-help-card" id="accounting">
        <h3>18. Accounting</h3>
        <p>Accounting holds accounts, money transfers, and statements. Set at least one default account so sales, returns, and expenses can post.</p>
    </div>

    <div class="w2k-help-card" id="letters">
        <h3>19. Letters</h3>
        <p>Letters → compose, approve, and send official letters with the club letterhead.</p>
    </div>

    <div class="w2k-help-card" id="invitations">
        <h3>20. Digital invitations</h3>
        <p>Digital Invitations → create an event, add guests, send WhatsApp/email invites with RSVP links.</p>
    </div>

    <div class="w2k-help-card" id="internships">
        <h3>21. Internships</h3>
        <p>Internships hub for programmes, enrolment, supervisor grading, and timesheets.</p>
    </div>

    <div class="w2k-help-card" id="tasks">
        <h3>22. Tasks, jobs, announcements, courses, timesheets</h3>
        <ul>
            <li><strong>Task Manager</strong> — assign and track internal work.</li>
            <li><strong>Job Board</strong> — public vacancies and applications.</li>
            <li><strong>Announcements</strong> — bulk WhatsApp messages.</li>
            <li><strong>Courses</strong> — course list, registrations, certificates.</li>
            <li><strong>TimeSheets</strong> — employees fill weeks; TimeSheet Admin reviews overtime.</li>
        </ul>
    </div>

    <div class="w2k-help-card" id="reports">
        <h3>23. Reports</h3>
        <p>Reports in the sidebar cover sales, purchases, and stock. Fixed Assets has its own report tabs.</p>
    </div>

    <div class="w2k-help-card" id="settings">
        <h3>24. Settings</h3>
        <p>The Settings hub tabs (General, Warehouse, Units, Tax, POS, Mail, …) end with <strong>Help</strong>. Use General Setting for company name, logo, and currency (RWF).</p>
    </div>

    <div class="w2k-help-card" id="transfer">
        <h3>25. Stock transfer</h3>
        <p>Transfer → Add Transfer to move stock between warehouses.</p>
    </div>

    <div class="w2k-help-card" id="whatsapp">
        <h3>26. WhatsApp links (quotations &amp; signatures)</h3>
        <p>Clients should land on the document or sign page — never the homepage.</p>
        <ol>
            <li>Send the quotation or contract from admin.</li>
            <li>The message has the URL on its own line (Review quotation / Open / Sign).</li>
            <li>If an old message still opens Home, <strong>resend</strong> it. Do not reuse the green link from before this fix.</li>
        </ol>
        <div class="w2k-help-tip">Every module now has a <strong>Help</strong> tab as the last tab in the row under the page title. The Help menu also sits at the bottom of the sidebar.</div>
    </div>
</div>

<script>
    $("ul#help-module").siblings('a').attr('aria-expanded','true');
    $("ul#help-module").addClass("show");
    $("ul#help-module #help-guide-menu").addClass("active");
    function w2kHelpNav() {
        var hash = (window.location.hash || '#start').replace('#','');
        $('.w2k-help-nav a').removeClass('is-active');
        var $a = $('.w2k-help-nav a[href="#' + hash + '"]');
        if (!$a.length) $a = $('.w2k-help-nav a').first();
        $a.addClass('is-active');
    }
    w2kHelpNav();
    $(window).on('hashchange', w2kHelpNav);
    $('.w2k-help-nav a').on('click', function () {
        $('.w2k-help-nav a').removeClass('is-active');
        $(this).addClass('is-active');
    });
</script>
@endsection
