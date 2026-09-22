@extends('layout.main')

@section('content')
@php $hv = \App\Support\AppVersion::erp(); @endphp
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
        <a href="#payments">Payments</a>
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
        <img class="w2k-help-shot" src="{{ url('public/branding/help/login.png') }}?v={{ $hv }}" alt="Kigali Expats Club sign-in card">
        <p class="w2k-help-cap">Live login: spinning club logo on a <strong>white</strong> disc with a gold ring, then <strong>Kigali Expats Club</strong>, Email or Username, Password, gold Forgot username or password, Login with WhatsApp OTP, and the black Sign in button. Version (W2K_V_…) is at the bottom of the card.</p>
        <ol>
            <li>Click <strong>Login</strong> in the public header (gold button, top right).</li>
            <li>Enter email / WhatsApp number / username and password, then Sign in.</li>
            <li>Or tap <strong>Login with WhatsApp OTP</strong> if you prefer a code.</li>
            <li>You land on the admin dashboard. The gold <strong>POS</strong> button is in the top bar if you sell.</li>
        </ol>
    </div>

    <div class="w2k-help-card" id="find-help">
        <h3>Where to find Help</h3>
        <p>Help is always one click away. Use the last horizontal tab on any module, the Help menu at the bottom of the sidebar (above your profile), or the Help icon in the top bar next to POS.</p>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/tabs-help.png') }}?v={{ $hv }}" alt="Rental Booking List with two-line coffee tabs and Help last">
        <p class="w2k-help-cap">Rental Module tabs wrap onto two lines: Booking Create, Booking List, Booking Request, Booked Products, Booking Reminder, Awaiting Signature, then Pending Review, Signed Contracts, and gold-outline <strong>Help</strong> last. Booking List has a date range and Submit.</p>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/sidebar-help.png') }}?v={{ $hv }}" alt="Sidebar with Help as the last menu item above the profile">
        <p class="w2k-help-cap">Scroll the black sidebar to the bottom. <strong>Help</strong> sits last, under Settings, just above your profile. Module tabs also end with Help (Expense Category, Expense List, Help).</p>
        <ol>
            <li>Work in any module (Product, Rental, Sale, Expense, …).</li>
            <li>Click the last tab, labelled Help.</li>
            <li>Or scroll the sidebar to Help, or use the question-mark Help in the header next to POS.</li>
        </ol>
    </div>

    <div class="w2k-help-card" id="website">
        <h3>2. Public website</h3>
        <p>Guests see Home, Menu, Events, About, and Become a Member in the black header (with EN / FR / RW). Those pages are edited in <strong>Site Content</strong> — not in Products.</p>
        <h4>Home</h4>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/home.png') }}?v={{ $hv }}" alt="Home page with three gold buttons over the Kigali night skyline">
        <p class="w2k-help-cap">Home hero: Welcome 2 Kigali Expats Club artwork, then three gold buttons — <strong>Join the Club</strong>, <strong>Events</strong>, and <strong>Cafe Menu</strong>. Language switcher EN / FR / RW is in the header. Version (W2K_V_…) is in the bottom-right of the hero.</p>
        <h4>Cafe menu (what guests order)</h4>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/menu.png') }}?v={{ $hv }}" alt="Cafe and Restaurant menu Coffee and Tea list">
        <p class="w2k-help-cap">Menu title is Cafe &amp; Restaurant. Category pills: Coffee &amp; Tea (active), Iced &amp; Specialty, Tea &amp; Hot Beverages, Fresh &amp; Detox Juices, Smoothies, Food. Espresso beverages include Espresso 2,000, Americano 2,000, Macchiato 2,000, Cortado 2,000, Flat White 2,500, Cappuccino 3,000, Café Latte 3,000, Caramel Macchiato 4,000, Café Mocha 4,000, and African Coffee 4,000. A photo sits on the right. Click a drink to add it to the cart.</p>
        <h4>About the club</h4>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/about.png') }}?v={{ $hv }}" alt="About page with vision, mission, and Mr. Sylvester Takwa">
        <p class="w2k-help-cap">About: Our Vision and Our Mission on cream, then Our Leadership with Mr. Sylvester Takwa, CEO / Director. Footer has Restaurant &amp; Cafe, Lounge &amp; Hospitality, contact +250 793 761 617, Kigali, Rwanda, and Join the Club.</p>
        <h4>Events</h4>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/events.png') }}?v={{ $hv }}" alt="Club Events page with Upcoming filter">
        <p class="w2k-help-cap">Club Events: search, Filter (Upcoming), Type (All types), Search. Empty state reads No Events Found — try a different filter or check back soon. Add events under Admin → Events and they appear here automatically.</p>
        <h4>Become a Member</h4>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/register.png') }}?v={{ $hv }}" alt="Become a Member choose a plan cards">
        <p class="w2k-help-cap">Become a Member → Choose a plan. Promotion: FREE membership for 90 days (0 FRW). Monthly 15,000 FRW, Quarterly 40,000 FRW, Annual 140,000 FRW. Members have 10% discount on all products. Tap a plan, then scan ID or passport to continue.</p>
    </div>

    <div class="w2k-help-card" id="products">
        <h3>3. Products — cafe items</h3>
        <p>Open <strong>Product</strong> in the sidebar. Tabs are Category, Product List, Add Product, Help.</p>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/products-list.png') }}?v={{ $hv }}" alt="Product List with African Coffee, African Tea, Americano">
        <p class="w2k-help-cap">Product List: + Add Product and Import Product buttons, then the table. Example rows: African Coffee W2K-010 (Coffee &amp; Tea, 4,000), African Tea W2K-029 (Tea &amp; Hot Beverages, 3,000), Americano W2K-002 (2,000), Avocado Smoothie W2K-048 (Smoothies, 5,000). Click a name, quantity, price, cost, brand, category, or unit to change it. Press Enter or click away to save.</p>
        <ol>
            <li>Search for <em>Espresso</em> or code <em>W2K-001</em>.</li>
            <li>Click the price cell if a drink changes (e.g. Cappuccino 3,000 FRW).</li>
            <li>Click quantity to set stock. That number is what POS and the list show.</li>
            <li>Use Action → Edit only when you need image, tax, or membership benefit.</li>
        </ol>
        <h4>Add a product</h4>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/add-product.png') }}?v={{ $hv }}" alt="Add Product form with gold fields">
        <p class="w2k-help-cap">Add Product (brown tab). Sections: The Item, Units &amp; Stock, Price. Gold-outline fields for type, name, code, barcode, brand, category, units, cost, price, quantity, and rent prices. Code is generated; tap the refresh icon to make a new one. Image is optional.</p>
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
        <p>Click the gold <strong>POS</strong> button in the top bar (next to Help).</p>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/pos.png') }}?v={{ $hv }}" alt="POS walk-in ticket with Cash Visa MoMo payment buttons">
        <p class="w2k-help-cap">POS: Walk-in Customer on the left, product search, ticket columns Product / Batch No / Price / Quantity / SubTotal. Right side: Category, Brand, Featured. Payment row at the bottom: Send to Payment, Cash, Visa, Credit, Group Credit, Airtel Money, MTN MoMo, Deposit. Cancel and Recent transaction sit under that.</p>
        <ol>
            <li>Choose Walk-in Customer or pick a member.</li>
            <li>Search a drink by name/code, or tap Category / Brand / Featured and add lines to the ticket.</li>
            <li>Take Cash, Visa, Credit, Airtel Money, or MTN MoMo.</li>
            <li>Online cafe orders from /menu also appear under Sale / Online Order.</li>
        </ol>
        <div class="w2k-help-tip">If Add Cash Register opens first, enter 0 (or the cash in the drawer) and submit. Then POS loads.</div>
    </div>

    <div class="w2k-help-card" id="content">
        <h3>6. Site Content (website words and menus)</h3>
        <p>Use <strong>Site Content</strong> in the sidebar for pages and menus. It does not change products, members, or sales.</p>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/site-content.png') }}?v={{ $hv }}" alt="Site Content Home tab with page and menu pills">
        <p class="w2k-help-cap">Site Content. Pages: Home, About, Services, Projects, Contact, Gallery, Menu, Events, Become a Member, Footer. Menus &amp; Order: Landing Menu, Side Bars, People, Admin settings order, Content Tabs. Admin modules: About Us Leaders, Digital Invitations, Internships, Reorder in Side Bars, Help last. Preview / View live page is on the right. Home — Content has Hero title and Hero subtitle.</p>
        <ul>
            <li><strong>Pages</strong> — Home buttons, About vision/mission, Events title, Menu title, Become a Member, Footer.</li>
            <li><strong>Landing Menu</strong> — show, hide, rename, reorder Home / Menu / Events / About / Become a Member.</li>
            <li><strong>Side Bars</strong> — same for the admin sidebar, including About Us Leaders, Digital Invitations, and Internships.</li>
            <li>Press Save on a tab, then open <a href="{{ url('/') }}" target="_blank" rel="noopener">the public site</a> to check.</li>
        </ul>
    </div>

    <div class="w2k-help-card" id="members">
        <h3>7. Membership</h3>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/membership.png') }}?v={{ $hv }}" alt="Membership dashboard with two-line tabs ending in Help">
        <p class="w2k-help-cap">Membership dashboard. Tabs wrap two lines: Dashboard, Applications, Members, Plans, Promotions, Benefits, Payments, Agreements, Documents, then Notifications, Reports, Audit, Settings, and <strong>Help</strong> last. Cards show Active, Expiring (30 days), Expired, Pending applications, Promotional, Paid members, Registration revenue, Renewal revenue, Free-product value, Promo → paid %.</p>
        <ol>
            <li>Guests apply on Become a Member (choose a plan, then scan ID).</li>
            <li>Staff review under Membership → Applications.</li>
            <li>Approved members can sign in and use POS benefits if you enabled them on a product.</li>
        </ol>
        <p>Settings (units, brands, tax, warehouse) stay under the <strong>Settings</strong> hub — not Site Content.</p>
    </div>

    <div class="w2k-help-card" id="rental">
        <h3>8. Rental module</h3>
        <p>Use <strong>Rental Module</strong> for equipment, accommodation, studio, or software bookings.</p>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/rental.png') }}?v={{ $hv }}" alt="Create Equipment Rental Booking with gold Customer Warehouse Biller pickers">
        <p class="w2k-help-cap">Create Equipment Rental Booking. Two-line tabs end with Help. Gold pickers: Customer, Warehouse (Main), Biller (Welcome 2 Kigali), and CC (Engineer / Company Copy). Default rental period has From / To Date &amp; Time and Apply to All Items. Equipment Selection is the product search. Order table sits at the bottom.</p>
        <ol>
            <li>Open Rental Module → Booking Create (first tab).</li>
            <li>Pick the customer. Their phone is required if you send a signature link.</li>
            <li>Set From / To dates, then add products with the search + button.</li>
            <li>To send the agreement on WhatsApp, tick send for signature and choose the contract type (equipment, accommodation, licenses, studio).</li>
            <li>Submit. The client gets a WhatsApp link. After they sign, staff countersign under Pending Review / Signed Contracts.</li>
        </ol>
        <div class="w2k-help-tip">Customer, Warehouse, Biller, and CC use the gold dropdowns — not the old blue ones. If Add Cash Register opens, enter 0 and submit, then continue the booking.</div>
    </div>

    <div class="w2k-help-card" id="quotations">
        <h3>9. Quotations</h3>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/quotation.png') }}?v={{ $hv }}" alt="Quotation List with status chips and Help tab">
        <p class="w2k-help-cap">Quotation List + Help. Status chips: Awaiting Signature, Client Quotes, Approval / Final, Rejected, Drafts. Table columns: Date, Reference, Biller, Customer, Supplier, Quotation Status, Client comment, Grand Total, Action. Help is also last in the sidebar under Settings.</p>
        <ol>
            <li>Quotation → Add Quotation. Choose the customer and add lines.</li>
            <li>Send via WhatsApp. The client opens <em>Review quotation</em>, then Sign &amp; Approve, Reject, or Quote.</li>
            <li>The official PDF goes out only after they sign. Track the row in the chips above.</li>
        </ol>
        <p>Every official WhatsApp and email shows the club name, a subject line, a <strong>serial number</strong> (for example W2K/MSG/26/0000042), and the date. Quote that serial if you follow up with a client.</p>
        <div class="w2k-help-tip">If an old WhatsApp message still opens Home, resend the quotation — do not reuse the old green link.</div>
    </div>

    <div class="w2k-help-card" id="contracts">
        <h3>10. Contracts</h3>
        <p>Contracts are sent from Rental (Awaiting Signature / Pending Review / Signed Contracts) or from Contracts in the sidebar.</p>
        <ol>
            <li>Create the booking or open Contracts → Create Contract.</li>
            <li>Add signatories with phone numbers. Issue the request.</li>
            <li>The client opens the WhatsApp link, reads the contract, draws a signature, and submits.</li>
            <li>Track status under Awaiting Signature, Pending Review, and Signed Contracts on the rental tabs.</li>
        </ol>
        <div class="w2k-help-tip">Clients do not need an account — they use the WhatsApp link. Staff must be signed in to send or countersign.</div>
    </div>

    <div class="w2k-help-card" id="expenses">
        <h3>11. Expenses</h3>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/expenses.png') }}?v={{ $hv }}" alt="Expense List with Help as last tab and last sidebar item">
        <p class="w2k-help-cap">Expense List. Tabs: Expense Category, Expense List, Help. Date range + Submit, then the table (Date, Reference No, Warehouse, Category, Product Category, Amount, Note, Action). Sidebar shows Expense selected and Help last under Settings.</p>
        <ol>
            <li>Expense → Expense List (or Expense Category to set categories first).</li>
            <li>Add an expense: category, warehouse, account, amount, and note.</li>
            <li>Submit. It appears on this list immediately.</li>
        </ol>
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
            <li>Guest taps a drink on Cafe &amp; Restaurant (Coffee &amp; Tea, Smoothies, …).</li>
            <li>Open Online Order → Online Order List.</li>
            <li>Open a row to see items, payment, and status.</li>
            <li>POS sales stay under Sale → Sale List. Online guest tickets stay here.</li>
        </ol>
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
        <p>Admin → Events. New events appear on the public Club Events page automatically (search + Upcoming filter).</p>
        <p>If the public page shows No Events Found, add an event in admin and keep the Upcoming filter, or switch the filter to see past dates.</p>
    </div>

    <div class="w2k-help-card" id="purchase">
        <h3>16. Purchases</h3>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/purchase.png') }}?v={{ $hv }}" alt="Purchase List with four tabs including Help">
        <p class="w2k-help-cap">Purchase List. Tabs only: Purchase List, Add Purchase, Import Purchase By CSV, Help. Date range + Submit, then the table (Date, Reference, Supplier, Purchase Status, Grand Total, Paid, Due, Payment Status, Action). There are no extra Add / Import buttons under the date filter.</p>
        <ol>
            <li>Purchase → Add Purchase (second tab) or Import Purchase By CSV.</li>
            <li>Supplier, warehouse, products, cost, and payment.</li>
            <li>Stock increases when the purchase is recorded.</li>
        </ol>
    </div>

    <div class="w2k-help-card" id="payments">
        <h3>17. Awaiting Payment</h3>
        <img class="w2k-help-shot" src="{{ url('public/branding/help/payments.png') }}?v={{ $hv }}" alt="Awaiting Payment list">
        <p class="w2k-help-cap">Awaiting Payment list: Customer Name, Unique ID, Order Id, Phone Number, Amount, Paid Amount, Due, Payment Status, Date, Add Payment. Use Add Payment on a row when a client settles a due balance.</p>
        <ol>
            <li>Open Sale → Awaiting Payment (or Payments in the sidebar).</li>
            <li>Find the customer and remaining Due.</li>
            <li>Click Add Payment, choose Cash / MoMo / card, and save.</li>
        </ol>
    </div>

    <div class="w2k-help-card" id="people">
        <h3>18. People</h3>
        <ul>
            <li><strong>Customers</strong> — needed for sales, rentals, quotations. Phone number is required for WhatsApp.</li>
            <li><strong>Users</strong> — staff logins and roles.</li>
            <li><strong>Billers / Suppliers</strong> — for invoices and purchases. Rental Biller defaults to Welcome 2 Kigali (Welcome 2 Kigali Expats Club).</li>
        </ul>
    </div>

    <div class="w2k-help-card" id="accounting">
        <h3>19. Accounting</h3>
        <p>Accounting holds accounts, money transfers, and statements. Set at least one default account so sales, returns, and expenses can post.</p>
    </div>

    <div class="w2k-help-card" id="letters">
        <h3>20. Letters</h3>
        <p>Letters → compose, approve, and send official letters with the club letterhead.</p>
    </div>

    <div class="w2k-help-card" id="invitations">
        <h3>21. Digital invitations</h3>
        <p>Digital Invitations → create an event, add guests, send WhatsApp/email invites with RSVP links.</p>
    </div>

    <div class="w2k-help-card" id="internships">
        <h3>22. Internships</h3>
        <p>Internships hub for programmes, enrolment, supervisor grading, and timesheets.</p>
    </div>

    <div class="w2k-help-card" id="tasks">
        <h3>23. Tasks, jobs, announcements, courses, timesheets</h3>
        <ul>
            <li><strong>Task Manager</strong> — assign and track internal work. Help is the last tab on that module too.</li>
            <li><strong>Job Board</strong> — public vacancies and applications.</li>
            <li><strong>Announcements</strong> — bulk WhatsApp messages.</li>
            <li><strong>Courses</strong> — course list, registrations, certificates.</li>
            <li><strong>TimeSheets</strong> — employees fill weeks; TimeSheet Admin reviews overtime.</li>
        </ul>
    </div>

    <div class="w2k-help-card" id="reports">
        <h3>24. Reports</h3>
        <p>Reports in the sidebar cover sales, purchases, and stock. Fixed Assets has its own report tabs.</p>
    </div>

    <div class="w2k-help-card" id="settings">
        <h3>25. Settings</h3>
        <p>The Settings hub tabs (General, Warehouse, Units, Tax, POS, Mail, …) end with <strong>Help</strong>. Use General Setting for company name, logo, and currency (RWF).</p>
    </div>

    <div class="w2k-help-card" id="transfer">
        <h3>26. Stock transfer</h3>
        <p>Transfer → Add Transfer to move stock between warehouses.</p>
    </div>

    <div class="w2k-help-card" id="whatsapp">
        <h3>27. WhatsApp links (quotations &amp; signatures)</h3>
        <p>Clients should land on the document or sign page — never the homepage.</p>
        <ol>
            <li>Send the quotation or contract from admin.</li>
            <li>The message has the URL on its own line (Review quotation / Open / Sign).</li>
            <li>If an old message still opens Home, <strong>resend</strong> it. Do not reuse the green link from before this fix.</li>
        </ol>
        <div class="w2k-help-tip">Every module now has a <strong>Help</strong> tab as the last tab in the row under the page title. The Help menu also sits at the bottom of the sidebar, under Settings.</div>
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
