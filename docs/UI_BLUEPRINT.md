# UI_BLUEPRINT.md

Page-by-page, component-by-component, interaction-by-interaction spec. Scope right now is limited to the three Phase 3 guest pages, since nothing past that is approved. Extended as later phases are approved.

Status: **Guest pages, authentication, Buyer Module, Vendor Module, Product Catalogue, Recommendation Engine, Shopping Workflow & Order Management, and Administration & Management implemented** — Homepage (Step 1), Login (Step 2), Registration (Step 3), Authentication (Step 4), Buyer Module (Step 6), Vendor Module (Step 7), Product Catalogue (Step 8), Recommendation Engine (Step 9), Shopping Workflow & Order Management (Step 10), Administration & Management (Step 11).
Last updated: 2026-07-03

---

## Branding note (resolved 2026-07-02)

The three supplied screenshots are **UI/UX references only** — layout, spacing, typography, colors, proportions, and the luxury editorial aesthetic are binding. They are **not** branding references. Every occurrence of the screenshots' placeholder brand **"AFRIQ THREADS"** is replaced with **"R&C Fashion"** below, with the visual treatment (logo mark position, wordmark styling, sizing) preserved exactly as shown. See [PROJECT_DECISIONS.md](PROJECT_DECISIONS.md) for the decision record.

Homepage copy uses the brief's explicit Kenyan-market copy (headline/subtitle/description below) rather than the screenshot's generic placeholder marketing line, since the screenshot's own trust-sidebar content ("M-Pesa & Cards", "Fast Delivery — Across Kenya") already confirms the Kenya-specific direction is correct. Login/Registration subtitle copy has no written override, so it's taken verbatim from the screenshots.

---

## Global Guest Navigation Rule

Top nav on every guest page: `Home · Men · Women · Accessories · Recommendations` + `Cart` + `Profile`.

**Updated 2026-07-02:** "Profile" added per the Step 1 brief — not present in the original screenshot, added as a plain text nav-link matching the existing style, placed after Cart. No visual precedent existed for it, so it was styled to match the other nav items rather than inventing a new treatment.

**Cart count:** the screenshot shows literal "Cart (2)" as static mockup content. For guests (no cart exists) this renders as **"Cart (0)"** — preserves the visual pattern without fabricating fake data. **Implemented as a real dynamic count for authenticated users as of Step 10** (`cart_count()` reads the buyer's persistent `Cart`, previously a session-array sum since Step 8).

Guest clicking **anything except Home** → redirected to Login. On successful login, redirect back to the originally intended destination (Laravel's standard `intended()` flow).

---

## Page 1 — Homepage (`/`) — ✅ Implemented 2026-07-02

Source: Screenshot 3 ("DESIGN 3 — AWARD-WINNING"). Route: `HomeController@index` → `resources/views/pages/home.blade.php`.

**Layout regions (desktop, left→right):**

1. **Top nav bar** — logo + "R&C Fashion" wordmark (top-left), center nav links (Home active/underlined, Men, Women, Accessories, Recommendations, Cart (0), Profile).
2. **Hero (left column)**
   - Headline, two lines, serif (Playfair Display): "Fashion That Defines" / "You." — "You." in Gold (`#C8A44D`), rest in Black (`#111111`).
   - Subtitle line: "Made for Kenyan fashion shopping."
   - Description paragraph: "Quality, individuality, and Kenyan craftsmanship—curated for the modern shopper, from vendors you can trust."
   - Button row: **Explore Collection** (solid black button) + **Get Outfit Suggestions →** (text-style link, gold, trailing arrow).
3. **Hero (right column)** — 3 editorial image cards in a row, each with a bottom-left caption overlay:
   - "Timeless Essentials" / "Shop Now →" (folded knitwear)
   - "Premium Footwear" / "Shop Now →" (sneaker)
   - "Finishing Touches" / "Shop Now →" (watch)
4. **Trust sidebar (far right)** — vertical stack of 4 icon + label + sublabel rows:
   - Curated Outfits — Handpicked for you
   - Premium Quality — Only the best
   - Secure Payments — M-Pesa & Cards
   - Fast Delivery — Across Kenya

**Guest behavior:** Explore Collection, Get Outfit Suggestions, Men, Women, Accessories, Recommendations, Cart all → `/login` (real page since Step 4). Home stays on the homepage (no-op).

**Authenticated behavior (Step 4, updated Step 8/9):** Explore Collection/Men/Women/Accessories now resolve to real catalogue pages (Step 8); Get Outfit Suggestions/Recommendations now resolve to the real `/recommendations` page (Step 9); Cart still resolves to `/coming-soon` (Step 10 territory) — see NAVIGATION_FLOW.md. "Profile" is replaced by the user's name + a Logout button once logged in.

**Recommended For You section — added Step 9, authenticated users only:** a `<x-recommendation-section>` renders below the hero, showing 8 hybrid-blended recommendations with their reason text, wrapped in `@auth` so guests never see it (there's no signal to base it on, and the section would otherwise render an honest-but-pointless empty state on every guest visit). Absent entirely for guests — not collapsed/empty, just not in the DOM.

**Components used:** Navbar, Button (primary + text-link variants), Editorial image card w/ caption overlay, Icon+label list item, `<x-recommendation-section>` (Step 9, authenticated only).

**Implementation notes / deviations:**
- **Copy ordering:** the Step 1 brief listed "Made for Kenyan fashion shopping" before the headline text. Implemented as Headline → Subtitle → Description (matching the screenshot's visual hierarchy, where the large serif headline is the dominant top element with no eyebrow text above it) rather than reordering to put the short line above the headline. Flag if an eyebrow-above-headline layout was actually intended.
- **Editorial card photography:** no real product photography has been sourced yet, so the three cards use CSS gradient placeholders (`--tone-1/2/3`, see DESIGN_SYSTEM.md) standing in for the screenshot's photography.
- **Cart/Profile:** see Global Guest Navigation Rule above for the "Cart (0)" and "Profile" decisions.

**Component Tree:**

```
<HomePage>                                          resources/views/pages/home.blade.php
├── <Navbar variant="full">                         components/navbar.blade.php
│   ├── <Logo /> + <Wordmark text="R&C Fashion" />
│   ├── <NavToggle />                                (mobile hamburger, ≤900px)
│   └── <NavLinks>
│       ├── <NavLink label="Home" active />
│       ├── <NavLink label="Men" />
│       ├── <NavLink label="Women" />
│       ├── <NavLink label="Accessories" />
│       ├── <NavLink label="Recommendations" />
│       ├── <NavLink label="Cart (0)" />
│       └── @auth: <span>{name}</span> + <LogoutForm> │ @else: <NavLink label="Profile" />
├── <HeroSection>
│   ├── <HeroCopy>
│   │   ├── <HeadlineDisplay lines={["Fashion That Defines", "You."]} accentWord="You." />
│   │   ├── <Subtitle text="Made for Kenyan fashion shopping." />
│   │   ├── <Description text="Quality, individuality, and Kenyan craftsmanship…" />
│   │   └── <ButtonRow>
│   │       ├── <Button variant="primary" label="Explore Collection" />
│   │       └── <Button variant="text-link" label="Get Outfit Suggestions" icon="arrow-right" />
│   ├── <EditorialCardGrid>                         components/editorial-card.blade.php
│   │   ├── <EditorialImageCard title="Timeless Essentials" cta="Shop Now" tone={1} />
│   │   ├── <EditorialImageCard title="Premium Footwear" cta="Shop Now" tone={2} />
│   │   └── <EditorialImageCard title="Finishing Touches" cta="Shop Now" tone={3} />
│   └── <TrustSidebar>                              components/trust-item.blade.php
│       ├── <TrustItem icon="hanger" label="Curated Outfits" sublabel="Handpicked for you" />
│       ├── <TrustItem icon="badge-check" label="Premium Quality" sublabel="Only the best" />
│       ├── <TrustItem icon="shield" label="Secure Payments" sublabel="M-Pesa & Cards" />
│       └── <TrustItem icon="truck" label="Fast Delivery" sublabel="Across Kenya" />
├── @auth: <RecommendationSection                   components/recommendation-section.blade.php — added Step 9
│     title="Recommended For You" module="home">
│     └── <RecommendationCard :result />×8           components/recommendation-card.blade.php
└── <Footer /> (deferred — not yet specified, see DESIGN_SYSTEM.md)
```

---

## Page 2 — Login (`/login`) — ✅ Implemented 2026-07-02

Source: Screenshot 2 ("DESIGN 3 — EDITORIAL LUXURY"). Routes: `GET /login` (`Auth\LoginController@create`) → `resources/views/pages/login.blade.php`; `POST /login` (`Auth\LoginController@store`, named `login.store`).

**Layout regions (single centered column, editorial product photography at the bottom of the page):**

1. **Top bar** — logo + "R&C Fashion" wordmark (left), "← Back to Home" (right, links to `/`).
2. **Heading block (centered)**
   - Headline, two lines, serif: "Continue Your Fashion" / "Journey" — "Journey" in gold.
   - Subtitle: "Sign in to your account and unlock a world of style."
   - Thin horizontal divider rule below.
3. **Form**
   - Label "Email Address" + input (placeholder "Enter your email", mail icon, right-aligned inside field).
   - Label "Password" + input (placeholder "Enter your password", eye/visibility-toggle icon).
   - Row: "Remember me" checkbox (left) — "Forgot Password?" gold link (right).
   - Primary button, full width, black bg, gold label: **SIGN IN →**
4. **Divider** — "OR" centered on a horizontal rule.
5. **Secondary path**
   - Text: "Don't have an account? **Create Account**" (Create Account is a gold inline link to `/register`).
   - Outlined button, full width, gold border/text, transparent bg, person-plus icon: **CREATE ACCOUNT** → `/register`.
6. **Footer imagery** — same editorial product photography block as Registration (shared partial).

**Validation behavior — implemented:** `LoginRequest` (Form Request) validates `email` (required, valid format) and `password` (required) inline, errors render under each field via `@error`. **Authentication is now real (Step 4):** `LoginRequest::authenticate()` calls `Auth::attempt()` (rate-limited: 5 attempts, then a lockout message with a countdown), `LoginController::store()` regenerates the session and redirects via `intended()`. "Remember me" is wired through to `Auth::attempt()`'s second argument. Route is `guest`-middleware protected — an already-authenticated user hitting `/login` is bounced to Home automatically.

**Components used:** Navbar (minimal variant), Input (text/email/password + icon), Checkbox, Button (primary + outline variants), Divider-with-label.

**"Forgot Password?" and "Create Account"** — ✅ both real. "Create Account" links to the real Registration page (Step 4). "Forgot Password?" links to `/password/forgot` (`password.request`), a real reset-link request form backed by Laravel's Password broker (Phase 11.5) — see NAVIGATION_FLOW.md for the full `password.request` / `password.reset` route pair.

**Component Tree:**

```
<LoginPage>                                         resources/views/pages/login.blade.php
├── <Navbar variant="minimal">                      components/navbar.blade.php
│   ├── <Logo /> + <Wordmark text="R&C Fashion" />
│   └── <TextLink label="← Back to Home" href="/" />
├── <AuthHeading                                    components/auth-heading.blade.php
│     lines={["Continue Your Fashion", "Journey"]}
│     subtitle="Sign in to your account and unlock a world of style."
│     divider />
├── <FlashStatus />                                 (session('status') banner, shown after form submit)
├── <LoginForm method="POST" action="/login">
│   ├── <InputField label="Email Address" type="email" name="email" icon="mail" />   components/input-field.blade.php
│   ├── <InputField label="Password" type="password" name="password" />              (renders both eye/eye-off icons, JS toggles visibility)
│   ├── <FormRow>
│   │   ├── <Checkbox label="Remember me" name="remember" />                         components/checkbox.blade.php
│   │   └── <TextLink label="Forgot Password?" href="/password/forgot" />
│   └── <Button variant="primary" fullWidth label="Sign In" icon="arrow-right" type="submit" />
├── <DividerWithLabel label="OR" />                 components/divider-with-label.blade.php
├── <SecondaryPath>
│   ├── <InlineText text="Don't have an account?" link={{label: "Create Account", href: "/register"}} />
│   └── <Button variant="outline" fullWidth label="Create Account" icon="person-plus" href="/register" />
└── <EditorialFooterImagery />                      components/auth-showcase.blade.php (reuses homepage card gradients)
```

---

## Page 3 — Registration (`/register`) — ✅ Implemented 2026-07-02

Source: Screenshot 1 ("DESIGN 1 — MINIMAL EDITORIAL"). Routes: `GET /register` (`Auth\RegisterController@create`) → `resources/views/pages/register.blade.php`; `POST /register` (`Auth\RegisterController@store`, named `register.store`).

**Initial state:** only the role-selection cards are visible. **No form fields render until a card is chosen.**

**Layout regions:**

1. Centered logo + "R&C Fashion" wordmark.
2. Headline, two lines, serif: "Begin Your Fashion" / "Journey" — "Journey" in gold.
3. Subtitle: "Create your account and unlock a world of style, quality and exclusive collections."
4. Section label: "JOIN AS" (small caps, centered, flanked by thin rules).
5. **Two role cards, side by side:**
   - **I Want To Shop** — light card, shopping-bag icon (gold), title, sublabel "Shop the best outfits and collections".
   - **I Want To Sell** — black card (visually "selected-looking" by default in the mock, but functionally both start unselected/inactive), storefront icon (gold), title (white), sublabel "Start your store and reach thousands" (light gray).
6. Below the cards: "ALREADY HAVE AN ACCOUNT?" label + "Sign in" gold link → `/login`.
7. Footer imagery — shared editorial product photography block.

**Interaction — accordion behavior — implemented as specified:**

- Clicking **I Want To Shop** → that card gets a gold border + lift (the "active" treatment — both cards use the same active treatment; "I Want To Sell" being black is just its normal resting style, not a selected state, per the note below), an accordion panel slides open beneath the cards containing the buyer signup form, and focus moves automatically to the first field.
- Clicking **I Want To Sell** → same behavior, but for the vendor form; buyer card remains visible and still clickable. Only one panel is ever open (mutually exclusive).
- Switching between the two is always possible, with no page reload. **Implemented in plain vanilla JS** (`resources/js/registration.js`), not Alpine — matches the "vanilla JavaScript only" stack constraint confirmed in Step 1/2, superseding this document's earlier Alpine.js placeholder note.
- Accordion animation: 350ms, ease-in-out — implemented via the **CSS grid `0fr`/`1fr` technique** (`grid-template-rows` transition on `.accordion-panel`), not a JS-measured `max-height` — more robust since the buyer and vendor forms have different content heights and neither needs a hardcoded guess.

**Form fields (buyer vs. vendor) — resolved 2026-07-02:**
- **Buyer:** Full Name, Email Address, Phone Number, Password, Confirm Password — all map directly to columns already on the `users` table (Step 1A), no new schema needed.
- **Vendor:** same fields **plus Store Name.** `store_name` does **not** have a backing column/table yet — DATABASE_BLUEPRINT.md's `vendor_profiles` table remains proposed-only. This field renders and validates (`RegisterRequest`) but nothing is persisted (see Validation behavior below).
- This was a judgment call, not a stopped-and-asked Level 3 decision — reasoned here: the buyer fields only use already-decided `users` columns, and the roadmap explicitly places "Database Design" (Phase 5) *after* "Registration Page" (this step) and "Authentication" (Phase 4), confirming real schema work — including whether `store_name` gets its own `vendor_profiles` table — is intentionally still open. Flag if a different field set was intended.

**Validation behavior — implemented:** `RegisterRequest` (Form Request) validates conditionally — `role` (required, `buyer` or `vendor`), `name`/`email`/`password` (required, `password` needs `password_confirmation` to match) always; `store_name` required only when `role = vendor`. **Account creation is now real (Step 4):** `RegisterController::store()` creates a real `users` row (password auto-hashed via the model's cast) and immediately logs the user in (`Auth::login()` + session regeneration), redirecting to Home with a welcome flash message. `store_name` is validated but **not persisted** — no `vendor_profiles` table exists yet (see DATABASE_BLUEPRINT.md). Vendors get no approval gate and full access immediately, same as buyers.

**Components used:** Navbar (minimal variant), Selectable role card, Accordion, Input (email/password/text), Button, inline link.

**Component Tree:**

```
<RegisterPage>                                      resources/views/pages/register.blade.php
├── <Navbar variant="minimal">
│   ├── <Logo /> + <Wordmark text="R&C Fashion" />
│   └── <TextLink label="← Back to Home" href="/" />
├── <AuthHeading                                    components/auth-heading.blade.php
│     lines={["Begin Your Fashion", "Journey"]}
│     subtitle="Create your account and unlock a world of style, quality and exclusive collections." />
├── <FlashStatus />                                 (session('status') banner, shown after form submit)
├── <DividerWithLabel label="JOIN AS" />             components/divider-with-label.blade.php
├── <RoleCardGroup>
│   ├── <RoleCard role="buyer" variant="light" icon="shopping-bag"           components/role-card.blade.php
│   │     title="I Want To Shop" sublabel="Shop the best outfits and collections" />
│   └── <RoleCard role="vendor" variant="dark" icon="storefront"
│         title="I Want To Sell" sublabel="Start your store and reach thousands" />
├── <AccordionPanel data-role-panel="buyer">         (CSS grid 0fr/1fr, JS toggles .is-open + .is-active)
│   └── <form action="/register"> — name, email, phone, password, password_confirmation, hidden role=buyer
├── <AccordionPanel data-role-panel="vendor">
│   └── <form action="/register"> — name, email, phone, store_name, password, password_confirmation, hidden role=vendor
├── <SecondaryPath>
│   ├── <SectionLabel text="ALREADY HAVE AN ACCOUNT?" />
│   └── <TextLink label="Sign in" href="/login" />
└── <EditorialFooterImagery />                       components/auth-showcase.blade.php
```

**Important implementation detail:** buyer and vendor fields share the same `name`/`id` in spirit (e.g. both have a "Full Name" field), but since both forms exist in the DOM simultaneously, each field's `id` is prefixed per form (`buyer-name`, `vendor-name`, etc.) while the `name` attribute stays plain (`name`, `email`, ...) since that's the POST key the Form Request expects. Duplicate `id`s across the two forms was a real bug caught during verification (broke label association and `getElementById` targeting) — fixed by adding an `id` prop to `input-field.blade.php`, defaulting to the field's `name` for pages with only one form (Login).

---

## Shared Footer Imagery Block

Appears at the bottom of Login and Registration (and possibly Homepage in a different arrangement). Three product shots on a stone/marble plinth: folded knitwear stack, a structured handbag, sunglasses, and a branded box. Purely decorative editorial photography — no interactive elements. The box in the photography should read "R&C Fashion" once real assets are sourced/shot. Actual photography assets are not yet sourced; placeholder treatment will be proposed at implementation time (Level 2).

---

## Authentication Redirect Flow

```
Guest → clicks gated nav/action → Login
Login → submits valid credentials → redirect to originally intended URL
Login → "Create Account" → Registration
Registration → completes chosen form → auto-login, redirect to Home with a welcome message (resolved Step 4)
```

---

## Page 4 — My Account (Buyer Module) — ✅ Implemented 2026-07-02 (Step 6)

**No screenshot reference exists for these pages** — the original three screenshots only covered Home/Login/Registration. Designed from scratch using DESIGN_SYSTEM.md's existing tokens/components (navbar, input-field, button, flash-status) rather than inventing new visual language, per "future pages must extend this same design language."

Three sub-pages share a tab nav (`<x-account-nav>`) and the full navbar variant (unlike Login/Registration's minimal variant, since these are logged-in pages where full site nav stays useful):

**`/account`** (`account.edit` / `account.update`) — profile view/edit: Full Name, Email Address (icon: mail), Phone Number, single "Save Changes" button. Pre-filled with the current user's data (not just post-validation `old()` — `input-field` gained a `:value` prop for this).

**`/account/addresses`** (`addresses.index` / `.store` / `.destroy` / `.default`) — list of the user's addresses as cards (label, "Default" badge if applicable, line1 + city, phone, "Set as Default"/"Remove" actions) followed by an "Add a New Address" form. First address added is automatically the default; deleting the default promotes the next-oldest remaining address.

**`/wishlist`** (`wishlist.index`) — ✅ real product grid (Phase 11.5). Reuses the exact `<x-product-grid>`/`<x-product-card>` components from the Product Catalogue (Step 8) — each entry shows image, vendor, name, rating, and price, and links through to the product page (where the existing wishlist toggle handles removal). Previously showed only a bare `<li>` product-name list with no images/links, a leftover from Step 6 written before Step 8's catalogue existed — fixed as part of the Phase 11.5 feature-completeness audit.

**Component Tree (shared across all three):**

```
<AccountPage>                                       resources/views/pages/account/{profile,addresses,wishlist}.blade.php
├── <Navbar variant="full" />
├── <h1>My Account</h1>
├── <AccountNav active="profile|addresses|wishlist"> components/account-nav.blade.php
│   ├── <TabLink label="Profile" href="/account" />
│   ├── <TabLink label="Addresses" href="/account/addresses" />
│   └── <TabLink label="Wishlist" href="/wishlist" />
├── <FlashStatus />
└── (page-specific content: profile form / address list + form / wishlist list or empty state)
```

**Authorization:** address update/delete go through `AddressPolicy` (`app/Policies/AddressPolicy.php`), checking `$address->user_id === $user->id` — verified with a live cross-user test (a second user's address correctly returned 403 Forbidden, not deleted). Profile/wishlist queries are scoped directly via `$request->user()->addresses()`/`wishlists()` rather than raw model lookups, so there's no ownership check to bypass in the first place.

**Deviations:** no profile photo upload (file storage/validation is separate scope, not touched). Adding wishlist items — originally deferred pending Step 8's product pages — has been implemented since Step 8 (the wishlist toggle lives on `/products/{product}`).

---

## Page 5 — Vendor Module — ✅ Implemented 2026-07-03 (Step 7)

**No screenshot reference** — same situation as the Buyer Module: designed from scratch using existing DESIGN_SYSTEM.md tokens/components, following a detailed written brief rather than a visual mockup.

Four sub-pages share a two-column layout (`<x-vendor-sidebar>` fixed-width left, content right — collapses to a horizontally-scrollable tab row under 900px) and the full navbar variant, reached via a new "Dashboard" nav-link visible only to authenticated vendors:

**`/vendor`** (`vendor.dashboard`) — store name + approval-status badge, a 4-card stat grid (Total/Active/Out-of-Stock Products, and Pending Orders — all real `COUNT()` queries; Pending Orders links through to `/vendor/orders` and reuses `OrderService`/`OrderRepository`, wired in Phase 11.5 once Step 10 gave it something real to count), and 3 quick-action cards (Add Product, Manage Products, Edit Store Profile).

**`/vendor/products`** (`vendor.products.index`) — paginated (10/page) table of the vendor's own products only, via `<x-product-table>` (thumbnail, name, category, price, stock, status badge, edit/delete actions) + `<x-empty-state>` when there are none.

**`/vendor/products/create`** / **`/vendor/products/{product}/edit`** — product form (name, category select, description textarea, price, stock, primary color, status select, multi-file image upload). Edit additionally shows existing images with individual "Remove" buttons above the form.

**`/vendor/store`** (`vendor.store.edit`) — store profile form (name, description, phone, email, county select populated from `config('kenya.counties')`, logo upload with a preview of the current logo if set).

**Component Tree (dashboard, representative of the shared layout):**

```
<VendorDashboardPage>                              resources/views/vendor/dashboard.blade.php
├── <Navbar variant="full" /> (shows "Dashboard" link since role === 'vendor')
├── <VendorSidebar active="dashboard">              components/vendor-sidebar.blade.php
│   ├── <TabLink label="Dashboard" href="/vendor" />
│   ├── <TabLink label="Products" href="/vendor/products" />
│   ├── <TabLink label="Store" href="/vendor/store" />
│   └── <TabLink label="Profile" href="/account" />  (reuses Step 6's Buyer Module page — not duplicated)
└── <VendorContent>
    ├── <StoreSummary> store name, <StatusBadge status="pending|approved|rejected" />
    ├── <FlashStatus />
    ├── <StatGrid> 4× <StatCard value label />
    └── <QuickActions> 3× <QuickActionCard title desc href />
```

**Authorization:** `ProductController`'s `edit`/`update`/`destroy`/`destroyImage` all call `$this->authorize(..., $product)` against `ProductPolicy` (`$user->id === $product->vendor_id`). `StoreController::update()` calls `$this->authorize('update', $vendorProfile)` against `StorePolicy` — technically redundant given the route has no ID parameter to tamper with (`/vendor/store`, not `/vendor/store/{id}`), but kept for defense-in-depth per the brief's explicit request. The whole `/vendor/*` group additionally requires the `vendor` middleware (`$user->role === 'vendor'`), which is a coarser "can this role see this section at all" check distinct from the Policies' "can this user touch this specific record."

**Verified live:** a second vendor account could not view, edit, or delete the first vendor's product (403 each time, product untouched); a buyer account got 403 on `/vendor` itself.

**Deviations:** image handling (upload/replace/delete) lives inside `ProductController` rather than a dedicated controller — tightly scoped to a single product's edit flow, not an independent resource. "Profile" in the vendor sidebar links to the existing `/account` page (Step 6) rather than a vendor-specific profile page — same personal-info form works for any role.

---

## Page 6 — Product Catalogue — ✅ Implemented 2026-07-03 (Step 8)

**No screenshot reference** — same situation as Buyer/Vendor Modules. Designed using existing tokens/components; new components (product-card, gallery, filter-sidebar, etc.) follow the same visual language.

**`/products`** (`products.index`) — "Shop All Products" heading, breadcrumb, an optional **Featured** section (products with `is_featured = true`, hidden entirely if none exist), a **Latest Products** grid with a filter sidebar (category/gender/price/colour/size) and pagination.

**`/products/{product:slug}`** (`products.show`) — breadcrumb (Home / Category / Product), image gallery (primary + thumbnails, click to swap — no zoom, no carousel library), name, rating (or "No reviews yet"), price, "Sold by: {store}" linking to the vendor's storefront, description, a specs table (category, colour, sizes, stock), **Add to Cart** (hidden entirely and replaced with "Out of Stock" text when `stock_quantity = 0`, rather than a disabled button — avoids an HTML footgun where a `disabled` attribute set to a falsy PHP value can still render as present), a wishlist toggle (♡ Add to Wishlist / ♥ Remove from Wishlist, reflecting real state, reusing Step 6's wishlist infrastructure), and — **added Step 9** — a "Similar Products" `<x-recommendation-section>` below the main product info (content-based item-item similarity, 6 items, always shown regardless of login-based personalization since it's product-to-product, not user-personalized).

**`/categories/{category:slug}`** (`categories.show`) — same layout as the index but pre-scoped to one category; the filter sidebar omits the Category dropdown here (redundant since the URL already fixes it) but keeps gender/price/colour/size.

**`/search`** (`search.index`) — search bar pre-filled with the current query, a result count ("N result(s) for 'term'"), same filter sidebar + grid + pagination as index. Visiting `/search` with no `q` shows a prompt to enter a term rather than silently listing all products (avoids implying "no results" or "everything matches" for a blank search).

**`/vendors/{vendor:store_slug}`** (`vendors.show`) — minimal public storefront: logo, store name, description, a grid of that vendor's published products. Deliberately not a full storefront (no vendor-specific filters, no vendor bio sections) — exists only so the "Sold by" link resolves to something real.

**Component Tree (catalogue index, representative):**

```
<CatalogIndexPage>                                  resources/views/catalog/index.blade.php
├── <Navbar variant="full" />
├── <Breadcrumb :items="[Home, Products]" />
├── <h1>Shop All Products</h1>
├── <FlashStatus />
├── <ProductGrid :products="$featured" />            (only rendered if non-empty)
└── <div class="catalog-layout">
    ├── <FilterSidebar :categories :colors :sizes :filters>
    │   ├── <select name="category_id" data-filter-auto>
    │   ├── <select name="gender" data-filter-auto>
    │   ├── <input name="min_price"> <input name="max_price">
    │   ├── <select name="color" data-filter-auto>
    │   └── <select name="size" data-filter-auto>
    └── <div class="catalog-content">
        ├── <ProductGrid :products="$products">
        │   └── <ProductCard :product>                each: image, vendor name, name, <RatingStars>, <PriceBadge>
        └── <Pagination :paginator="$products" />      (reused as-is from Step 7)
```

**Filter behavior:** `<select>` filters auto-submit on `change` (vanilla JS, `resources/js/filters.js`); price min/max require pressing "Apply Filters" (auto-submitting on every keystroke of a number input would be annoying). All filters are plain GET query params — URLs are bookmarkable/shareable, no JS required for the filtering itself to function.

**"Gender" filter clarification:** there's no `gender` column — it's a lookup against the existing `Men`/`Women` categories (seeded Step 5). Selecting "Men" is equivalent to filtering `category.name = 'Men'`.

**Deviations:** no numbered pagination (Previous/Page X of Y/Next only, matching the existing Step 7 pagination component); size filter options are a static list (XS–XXL) rather than derived from actual product data (deriving distinct values from a JSON column portably wasn't worth the complexity); "available colours" reuses the existing single `primary_color` field rather than becoming a multi-value list like sizes did, since expanding it wasn't necessary to satisfy the requirement.

---

## Page 7 — Recommendations — ✅ Implemented 2026-07-03 (Step 9)

**No screenshot reference** — designed from scratch using existing tokens/components (breadcrumb, flash-status, empty-state, the new recommendation-card/section), same approach as the Buyer/Vendor Modules and Product Catalogue.

**`/recommendations`** (`recommendations.index`) — breadcrumb (Home / Recommendations), "Recommended For You" heading + subtitle, an **algorithm switcher** (Hybrid / Content-Based / Collaborative / Trending pills, `?algorithm=` query param — the brief's "run each algorithm independently... for academic comparison" requirement, exposed directly in the UI rather than only via the CLI evaluator), then either a 24-item recommendation grid or an empty state ("No recommendations yet... browse products, add items to your wishlist, or make a purchase").

**`/recommendations/click/{product}`** (`recommendations.click`) — not a page, a tracking redirect. Every recommendation card everywhere in the app links here (with a `module` query param identifying where the click came from) instead of straight to the product; the controller logs the click and redirects to the real product page. Invisible to the user — the redirect is instant.

**Component Tree:**

```
<RecommendationsPage>                               resources/views/pages/recommendations.blade.php
├── <Navbar variant="full" />
├── <Breadcrumb :items="[Home, Recommendations]" />
├── <FlashStatus />
├── <h1>Recommended For You</h1>
├── <p class="recommendations-subtitle">
├── <AlgorithmSwitch>                                (nav, 4 pill links, active = current ?algorithm=)
│   ├── <a href="?algorithm=hybrid">Hybrid (Default)</a>
│   ├── <a href="?algorithm=content">Content-Based</a>
│   ├── <a href="?algorithm=collaborative">Collaborative</a>
│   └── <a href="?algorithm=popularity">Trending</a>
└── <EmptyState v-if="empty"> │ <RecommendationGrid v-else>
    └── <RecommendationCard :result module="recommendations_page"> ×N   components/recommendation-card.blade.php
```

**Deviations:** no per-algorithm empty-state copy (a cold-start Content-only or Collaborative-only view shares the same generic empty-state message as a genuinely empty Hybrid view) — flagged in DESIGN_SYSTEM.md's Future Improvements. No pagination on this page (`recommendation_limit`/24-item cap instead) since recommendation lists are meant to be a curated top-N, not a browsable catalogue — unlike `/products`, showing "page 2 of recommendations" would undercut the "these are the best matches" framing.

---

## Page 8 — Cart, Checkout & Orders — ✅ Implemented 2026-07-03 (Step 10)

**No screenshot reference** — same approach as every post-Registration page: built from existing tokens/components, following the detailed Step 10 brief rather than a visual mockup.

**`/cart`** (`cart.index`) — a two-column layout (items list + order summary), matching the `.cart-layout` grid also used by Checkout. Each line is an `<x-cart-item>` (thumbnail, name, price, `−`/qty/`+`, line total, Remove) followed by a "Clear Cart" link. Empty state reuses `<x-empty-state>` with an "Explore Collection" CTA — no separate `empty-cart` component (see DESIGN_SYSTEM.md).

**`/checkout`** (`checkout.index`/`.store`) — shipping address fields (pre-filled from the buyer's default `Address` and name/phone if set), a delivery-option select (Standard/Express, price shown inline in the option label), a payment-method select (Card placeholder / Cash on Delivery), and an order summary sidebar listing every cart line + subtotal. Submitting runs full server-side validation (`CheckoutRequest`) plus a final stock re-check (`CartService::validateForCheckout()`) before an order is created — any stock conflict since the item was added to cart surfaces as a visible error via `<x-flash-status>`, not a silent failure.

**`/orders`** (`orders.index`) — "My Orders" list, each row an `<x-order-card>` (order number, date, all 3 status badges, item count, total), paginated, linking through to the order detail.

**`/orders/{order}`** (`orders.show`) — doubles as both "Order Details" and the post-checkout "Order Confirmation" (differentiated only by a one-time flash message, not a separate template — see PROJECT_DECISIONS.md). Shows the 3-step Order Tracking stepper, all 3 status badges, line items with per-item fulfillment status, the shipping address, delivery/payment method, and a totals breakdown (subtotal/shipping/tax/total).

**Component Tree (cart page, representative of the shared `.cart-layout` pattern):**

```
<CartPage>                                          resources/views/pages/cart.blade.php
├── <Navbar variant="full" />
├── <Breadcrumb :items="[Home, Cart]" />
├── <FlashStatus />                                 (now also renders validation errors — see DESIGN_SYSTEM.md)
├── <h1>Your Cart</h1>
└── <EmptyState v-if="empty"> │ <div class="cart-layout"> v-else
    ├── <div class="cart-items">
    │   ├── <CartItem :item /> ×N                    components/cart-item.blade.php
    │   └── <ClearCartForm>
    └── <CartSummary :totals checkout-href>          components/cart-summary.blade.php
```

**Deviations:** no separate "Order Confirmation" template (see above). No saved-address picker at checkout — only one auto-filled form, no way to choose between multiple saved addresses (flagged in NAVIGATION_FLOW.md). No quantity direct-input on cart items — only +/− stepper buttons.

## Page 9 — Vendor Orders — ✅ Implemented 2026-07-03 (Step 10)

**No screenshot reference.** Extends the existing Vendor Module layout (`<x-vendor-sidebar>`, now with an added "Orders" tab) rather than inventing a new shell.

**`/vendor/orders`** (`vendor.orders.index`) — a `<x-product-table>`-style table (reused markup/class, not the component itself, since the columns differ: Order / Product / Qty / Total / Fulfillment / Actions) of every order item belonging to the vendor's own products, paginated, most recent first.

**`/vendor/orders/{order}`** (`vendor.orders.show`) — the same order a buyer sees, but the item list is filtered server-side (`OrderService::vendorItemsInOrder()`) to only the vendor's own line items within that order — a vendor never sees another vendor's products or pricing even if they share an order. Each row has an inline fulfillment-status `<select>` that auto-submits on change (`data-fulfillment-auto`, same pattern as Step 8's filter auto-submit).

**Deviations:** no dedicated "customer details" sub-page — the shipping name/address/phone are shown as a single summary line above the item table, matching what a vendor actually needs (where to ship, not a full customer profile).

---

## Page 10 — Administration & Management — ✅ Implemented 2026-07-03 (Step 11)

**No screenshot reference** — same approach as every post-Registration page: built from existing tokens/components, following a detailed written brief. Nine sub-areas share a two-column layout (`<x-admin-sidebar>` fixed-width left, content right — same collapsing-to-horizontal-tabs pattern under 900px as the Step 7 Vendor Sidebar) and the full navbar variant, reached via a new "Admin" nav-link visible only to `role === 'admin'` users.

**`/admin`** (`admin.dashboard`) — 6 summary cards (Total Users/Vendors/Products/Orders/Revenue/Recommendation CTR), a notification center (only non-zero alerts render: pending vendor approvals, pending product moderation, low/out-of-stock products, suspended vendors, failed recommendation evaluations), a 🟢/🟡/🔴 system-health badge, and 6 `<x-bar-chart>` charts (Orders/Revenue/New Users/Vendor Registrations per month, Recommendation Clicks per month, Best Selling Categories).

**`/admin/users`** (`admin.users.index`) — search/filter (name/email, role, status) table with Suspend/Activate and Make Admin actions. No profile/address editing — administrators can only change `status` and `role`, never a user's own information.

**`/admin/vendors`** (`admin.vendors.index` / `.show`) — search/filter by approval status; each row has inline Approve/Reject or Suspend/Restore mini-forms (a text input + button, not a full page) requiring a reason; the detail page additionally shows per-vendor statistics (product/published-product/order counts, total revenue).

**`/admin/products`** (`admin.products.index`) — search/filter by status; inline Approve/Reject/Hide/Archive/Restore mini-forms, each requiring a reason. No product-editing fields anywhere on this page — administrators moderate, they do not edit vendor listings.

**`/admin/categories`** (`admin.categories.index` / `.create` / `.edit`) — the one admin area with a real create/edit form (name, parent select, display order) rather than inline moderation actions, plus Archive/Restore. Archived (soft-deleted) rows stay visible in the list at reduced opacity with only a Restore action, rather than disappearing — an administrator needs to see what's archived to restore it.

**`/admin/reports`** (`admin.reports.index`) — a report-type select (Users/Vendors/Products/Orders/Revenue/Best Selling Products/Best Selling Categories/Recommendation Statistics) + date-range inputs + a "Run Report"/"Export CSV" pair. The results table's columns change entirely per report type (rendered generically from whatever associative-array keys the selected report returns, not per-type Blade templates).

**`/admin/recommendation-analytics`** (`admin.recommendation-analytics.index`) — overview cards (Generated/Clicks/CTR/Generated Today/Cold Start Users/Hybrid Usage), an Algorithm Usage table, an Evaluation Metrics table (live `RecommendationEvaluator::evaluate()` output for all 4 algorithms), and 4 small tables (Most Recommended/Most Clicked/Highest CTR/Lowest CTR Products). Entirely read-only — no control on this page can change how a recommendation is generated.

**`/admin/audit-logs`** (`admin.audit-logs.index`) — filterable (Administrator/Action/Entity type/date range) table: timestamp, administrator, action, entity (`{ModelClass}: {name}`), a plain-language changes summary (`field: old → new`), reason.

**`/admin/settings`** (`admin.settings.edit`) — one form: Site Name, two Delivery Cost number inputs, three Recommendation Weight number inputs (with a note that the engine redistributes them proportionally, no manual sum-to-1 requirement), and a Maintenance Mode checkbox.

**`/admin/health`** (`admin.health.index`) — 🟢/🟡/🔴 badges for Database/Cache/Storage/Queue, a Failed Jobs count, the Application Version string, and a second "Data Overview" card row (Users/Vendors/Products/Orders/Recommendations counts). Entirely read-only.

**Component Tree (dashboard, representative of the shared layout):**

```
<AdminDashboardPage>                                resources/views/admin/dashboard.blade.php
├── <Navbar variant="full" /> (shows "Admin" link since role === 'admin')
├── <AdminSidebar active="dashboard">                components/admin-sidebar.blade.php
│   ├── <TabLink label="Dashboard" href="/admin" />
│   ├── <TabLink label="Users" href="/admin/users" />
│   ├── <TabLink label="Vendors" href="/admin/vendors" />
│   ├── <TabLink label="Products" href="/admin/products" />
│   ├── <TabLink label="Categories" href="/admin/categories" />
│   ├── <TabLink label="Reports" href="/admin/reports" />
│   ├── <TabLink label="Recommendation Analytics" href="/admin/recommendation-analytics" />
│   ├── <TabLink label="Audit Logs" href="/admin/audit-logs" />
│   ├── <TabLink label="Settings" href="/admin/settings" />
│   └── <TabLink label="System Health" href="/admin/health" />
└── <AdminContent>
    ├── <FlashStatus />
    ├── <NotificationCenter>                         only rendered rows whose count > 0
    ├── <SummaryGrid> 6× <SummaryCard value label />
    └── <ChartGrid> 6× <BarChart title data unit? /> components/bar-chart.blade.php
```

**Authorization:** the entire `/admin/*` group requires both `auth` and a new `admin` middleware (`EnsureUserIsAdmin`), which aborts 403 unless `$user->role === 'admin'` **and** `$user->status === 'active'` — a suspended administrator is locked out exactly like a suspended buyer/vendor. This is a coarser "can this role see this section at all" check, the same pattern as the Step 7 `vendor` middleware; no per-record Policy exists in the admin area since administrators supervise every record, not a subset they own.

**Verified live:** a buyer account got 403 on every `/admin/*` route tested; every moderation action (suspend/activate/assign-admin, approve/reject/suspend/restore, approve/reject/hide/archive/restore, archive/restore) round-tripped correctly against real seeded data with its effect visible immediately in the same page's table; CSV export confirmed via a direct `fetch()` check of the `Content-Type: text/csv` response header; Settings changes confirmed to take effect immediately (`config()` read back correctly inside `tinker`) without any Recommendation Engine or Checkout code needing to change; Maintenance Mode confirmed to block a credential-less visitor while leaving the admin's own session unaffected.

**Deviations:** Category is the only admin area with a full create/edit form rather than inline moderation actions, since "Create/Edit" was the brief's own explicit requirement for categories specifically (every other entity's admin actions are moderation, not editing — see PROJECT_DECISIONS.md for why administrators never edit user/vendor/product fields directly). Reports render generically from whatever columns a report type returns rather than 8 separate per-type templates, avoiding near-duplicate Blade files for what is structurally the same "select → filter → table → export" page 8 times over.

---

## Not Yet In Scope

Real payment gateways, refunds, coupons, gift cards, loyalty points, subscriptions, shipping APIs, AI upselling/cross-selling, multi-vendor checkout splitting, marketing emails, and inventory forecasting were all explicitly out of Step 10's scope — see PROJECT_DECISIONS.md. "Complete Your Outfit", "Recommended Vendors", "Recommended Collections" and other rule-based recommendation modules sketched in the original RECOMMENDATION_ENGINE.md plan were explicitly out of Step 9's scope — see that document for the full list. An AI Admin Assistant, Business Intelligence integrations, WebSockets/push/email notifications, real-time dashboards, and scheduled-job/cron monitoring were all explicitly out of Step 11's scope — the Dashboard's Notification Center is a read-only page-load snapshot, not a live push feed. These get their own sections here once their phases/scope are reached.

---

## Future Improvements

Ideas surfaced during planning that are intentionally postponed, not forgotten:

- Dark-mode variant of the design system (not requested, but the color palette would need contrast-checking if it's ever wanted).
- Mobile-specific component tree breakdowns — this document currently assumes desktop layout; mobile breakpoints/stacking order need their own pass once desktop is approved and built.
- Skeleton/loading states for the three guest pages (not visible in the static screenshots).
- Empty-state and error-state visuals for the Login/Registration forms beyond basic validation text.
- Localization (English/Kiswahili toggle) — out of scope for now, no signal in the brief that it's needed, but worth flagging given the Kenyan market focus.
- Real photography sourcing/shoot plan for the shared footer imagery block (currently using the supplied screenshot photography as a stand-in reference).
- No feature/route tests exist for Login yet (`GET /login` renders, `POST /login` validates + redirects) — worth adding once Phase 4 gives the form something real to assert against.
- "Remember me" checkbox — **implemented (Step 4)**, wired through to `Auth::attempt()`'s remember argument.
- Registration's account creation — **implemented (Step 4)**; `store_name` still isn't persisted anywhere — revisit once Database Design (Step 5) settles the buyer/vendor schema, especially whether it gets its own `vendor_profiles` table.
- No email verification — `email_verified_at` exists on `users` but nothing sets it or gates access on it. **Resolved (Phase 11.5): intentionally out of scope**, not a gap — `App\Models\User` deliberately never implements `MustVerifyEmail` (the import is commented out), and no UI ever references a verification step. See PROJECT_DECISIONS.md.
- Vendor approval workflow doesn't exist — vendors get full access immediately upon registration, same as buyers. Revisit once `vendor_profiles`/`approval_status` exists.
- No feature/route tests exist for the auth flow yet (login success/failure/rate-limit, register + auto-login, logout, guest-middleware bounce) — all verified manually in-browser during Step 4, worth codifying as PHPUnit tests before Phase 10.
- No feature/route tests exist for Registration yet — same as Login, worth adding once there's real persistence to assert against.
- `shopping-bag`/`storefront` icons are simple hand-drawn approximations, not refined illustrations — fine for now, worth a visual pass once real product photography is sourced.
- No profile photo upload UI (Step 6) — `users.profile_photo` stays unset until this is designed.
- **Wishlist page — implemented Step 8 (add/remove), rendering fixed Phase 11.5** (now uses the real `<x-product-grid>` instead of a bare name list), resolving this exact note.
- No feature/route tests for the Buyer Module (Step 6) — verified manually in-browser (profile update, address CRUD + set-default, cross-user 403 check), same testing debt as prior steps.
- No feature/route tests for the Vendor Module (Step 7) — same debt, verified manually (product/store CRUD, image upload/remove, cross-vendor 403, vendor-middleware 403 for buyers).
- Vendor product edit has no in-place field for reordering images (`display_order` exists on `product_images` but nothing in the UI lets a vendor drag-reorder them yet) — new images just append after existing ones.
- Uploaded product/logo images aren't resized or compressed — stored at whatever size the vendor uploads. Fine for now, flagged in DEVELOPMENT_RULES.md too.
- Vendor sidebar's horizontal-scroll behavior on mobile (<900px) has no visual affordance hinting it's scrollable (no fade/arrow) — functional but a bit undiscoverable; minor polish item.
- No feature/route tests for the Product Catalogue (Step 8) — verified manually (pagination, filters, search, wishlist/cart toggle, gallery). Same testing debt as every step so far.
- **Cart view — implemented Step 10** (`/cart`, full add/increase/decrease/remove/clear), resolving this exact note.
- Vendor storefront (`/vendors/{vendor}`) has no filters/sub-navigation of its own — intentionally minimal per the brief, but would benefit from at least a category filter if vendors grow larger catalogues.
- Size filter options are a hardcoded list, not derived from actual `products.sizes` data — could show "sizes with zero matching products" as selectable options, which is slightly misleading at scale.
- No feature/route tests for the Recommendation Engine (Step 9) — verified manually via `tinker`, `php artisan recommendations:evaluate`, and in-browser interaction. Flagged as the most consequential testing gap so far in DEVELOPMENT_RULES.md, since recommendation scoring correctness matters for the dissertation defense specifically.
- Recommendations page has no per-algorithm empty-state copy — a Content-only or Collaborative-only view with no signal shows the same generic message as a genuinely empty Hybrid result.
- "Complete Your Outfit", "Recommended Vendors", "Recommended Collections", and "Recently Viewed" (as standalone UI modules) remain unbuilt — deliberately out of Step 9's scope, see RECOMMENDATION_ENGINE.md.
- No recency-decay weighting for interactions (a view from a year ago counts the same as one from yesterday) — flagged as a contained follow-up in RECOMMENDATION_ENGINE.md.
- No feature/route tests for Cart/Checkout/Orders (Step 10) — verified manually via `tinker` and in-browser interaction (add/increase/decrease/remove/clear, over-limit stock validation, full checkout, fulfillment updates). Same testing debt as every step, worth batching together before Phase 10 (Testing) proper.
- Checkout has no saved-address selector — only one auto-filled shipping form, no way to pick between multiple saved addresses (see NAVIGATION_FLOW.md).
- No order cancellation or refund flow — explicitly out of Step 10's scope; `order_status` currently only ever reaches `processing` → `completed`, never `cancelled` in practice (the column supports it, nothing sets it).
- **Admin area — implemented Step 11**, resolving the "No admin pages" note that used to sit in "Not Yet In Scope." No feature/route tests exist for it yet — verified manually in-browser (every CRUD/moderation action, CSV export, settings round-trip, maintenance mode, health dashboard), same testing debt as every prior step.
- No "revoke admin" UI — the Users page can only promote, never demote, an administrator (see PROJECT_EVOLUTION.md).
- Reports page has no saved/scheduled report concept — every run is ad hoc, re-querying live data; fine at this data volume, would need revisiting if report generation ever became slow enough to warrant caching or a background job.
- Audit Log's "changes" column is a plain `field: old → new` string built from whatever's in `old_values`/`new_values` — no diff highlighting or per-field-type formatting (e.g. a boolean shows `1`/`0` rather than `Yes`/`No`). Fine for an administrator reading their own recent actions, would benefit from friendlier formatting if the audit log ever becomes a primary support tool.
- Bar Chart has no interactive tooltip — every value is always visible as static text, which works at the current small-dataset scale (6 months, handful of categories) but would get visually busy with a much longer series.
- Vendor Orders has no bulk fulfillment-status update — each line item's status is updated one at a time via its own inline `<select>`.
