# DESIGN_SYSTEM.md

Reusable visual language for the whole platform. Built before any page logic (Phase 2), consumed by every page after. Nothing here is business logic — pure presentation.

Status: **Guest pages, authentication, Buyer Module, Vendor Module, Product Catalogue, Recommendation Engine, Shopping Workflow & Order Management, and Administration & Management implemented** — Home (Step 1), Login (Step 2), Registration (Step 3), Authentication (Step 4), Buyer Module (Step 6), Vendor Module (Step 7), Product Catalogue (Step 8), Recommendation Engine (Step 9), Shopping Workflow & Order Management (Step 10), Administration & Management (Step 11).
Last updated: 2026-07-03

---

## Colors

**Updated 2026-07-02** — palette revised per direct Product Owner spec for Step 1 implementation. Supersedes the original draft values (Champagne Gold `#C8A96A`, Warm White `#FAF8F5`, Dark Charcoal `#2C2C2C`).

| Role | Name | Hex |
|---|---|---|
| Primary | Black | `#111111` |
| Secondary | Gold | `#C8A44D` |
| Secondary (hover/soft) | Soft Gold | `#D8B55B` |
| Background | Ivory | `#F8F6F2` |
| Accent | Beige | `#EFE9DD` |
| Border | Border | `#D8D2C8` |
| Cards | Pure White | `#FFFFFF` |
| Text (primary) | — | `#111111` |
| Text (muted) | Muted Text | `#666666` |
| Error | — | `#B3261E` — **added 2026-07-02**, needed for form validation states (Login's email/password errors); not in the original brief's palette, chosen as a muted brick red consistent with the editorial-luxury tone rather than a bright alert red |

Usage conventions (Level 1, revisit if a page proves it wrong):
- Black → primary buttons, active nav underline, headline text, dark card backgrounds (e.g., editorial image cards, "I Want To Sell" card), footer.
- Gold / Soft Gold → accent word in headlines, links, icons on light backgrounds, button label accents, outline-button border/text; Soft Gold for hover states.
- Ivory → page background.
- Pure White → card surfaces that need to sit distinctly above the ivory background (not used by the editorial image cards, which are dark).
- Beige → secondary surface / subtle section backgrounds, hover states (e.g. outline button hover fill).
- Border → hairline dividers (navbar bottom border, nav-item underlines).
- Muted Text → body/description copy and sublabels — softer than the primary black used for headings.

## Typography

| Use | Family |
|---|---|
| Headings | Playfair Display (serif) |
| Body | Inter |
| Buttons | Inter SemiBold |

Editorial luxury hierarchy: large serif display headlines, restrained sans-serif body copy, no more than two typefaces on any page.

Suggested scale (Level 1, to be implemented as CSS custom properties in Phase 2):

| Token | Size | Line-height | Use |
|---|---|---|---|
| `--text-display` | 48–56px | 1.1 | Hero/page headlines (Playfair Display) |
| `--text-h2` | 32px | 1.2 | Section headings |
| `--text-h3` | 22px | 1.3 | Card titles |
| `--text-body` | 16px | 1.6 | Paragraph copy |
| `--text-small` | 14px | 1.5 | Labels, captions, form hints |
| `--text-micro` | 12px | 1.4 | Badges, uppercase eyebrow labels |

## Spacing System

8px base unit, standard multiples — matches the generous whitespace in the screenshots:

| Token | Value |
|---|---|
| `--space-1` | 8px |
| `--space-2` | 16px |
| `--space-3` | 24px |
| `--space-4` | 32px |
| `--space-5` | 48px |
| `--space-6` | 64px |
| `--space-7` | 96px |

## Buttons

| Variant | Background | Text | Border | Use |
|---|---|---|---|---|
| Primary | `#111111` | `#FFFFFF` or `#C8A96A` label accents | none | Sign In, Explore Collection |
| Outline | transparent | `#C8A96A` | 1px `#C8A96A` | Create Account |
| Text link | transparent | `#C8A96A` | none, often with trailing → | Get Outfit Suggestions, Shop Now |

States: default → hover (slight lighten/darken + optional lift) → active → disabled (reduced opacity, no pointer). Transition: **200ms**, ease-in-out, `opacity`/`transform`/`background-color`.

## Cards

Pure white surface, soft shadow, generous internal padding (`--space-4`–`--space-5`). Two documented variants so far:
- **Role-selection card** (Registration) — light or dark background, centered icon + title + sublabel, whole card is clickable.
- **Editorial image card** (Homepage) — image fill, bottom-left text overlay on a gradient scrim. **Implemented 2026-07-02** using CSS gradient placeholders (`--tone-1/2/3` in `resources/css/components/cards.css`) since no real product photography has been sourced yet — replace with actual photography without touching layout/scrim/caption CSS once assets exist.

Transition: **250ms**, ease-in-out, `transform`/`box-shadow` on hover.

## Input Fields

Bordered rectangle, label above, placeholder inside, optional leading/trailing icon (mail, lock/eye for password). Focus state: border color shifts to Gold, subtle outer glow (`0 0 0 3px rgba(200,164,77,0.15)`).

**Implemented 2026-07-02** (`resources/views/components/input-field.blade.php`, styles in `resources/css/components/auth.css`): password fields render both the `eye` and `eye-off` icons and toggle visibility via a `.is-active` class swap (JS only flips input `type` and toggles the class — no SVG manipulation in JS, keeping icon markup single-sourced in the Blade `<x-icon>` component). Validation errors render via `@error()` below the field in the new Error color (`#B3261E`). Login's `LoginRequest` Form Request is the first real use of this — required/email-format on `email`, required on `password`.

## Checkbox

**Implemented 2026-07-02** (`resources/views/components/checkbox.blade.php`) — native `<input type="checkbox">` styled via `accent-color: var(--color-gold)` rather than a fully custom-built checkbox control. First use: Login's "Remember me."

## Accordion

Used for the Registration role-switch. **350ms**, ease-in-out. Expands to reveal a form panel beneath the selected role card; focus moves to the first field on expand.

**Implemented 2026-07-02** (`resources/css/components/registration.css`, `resources/js/registration.js`): uses the CSS grid `grid-template-rows: 0fr` → `1fr` technique (with an `overflow:hidden` inner wrapper) rather than animating `height`/`max-height` directly — handles the buyer and vendor forms' different content heights without a hardcoded guess. JS only toggles an `.is-open` class and moves focus; no height measurement in JS. Plain vanilla JS, not Alpine (see DEVELOPMENT_RULES.md's Step 1 resolution).

## Role Card

**Implemented 2026-07-02** (`resources/views/components/role-card.blade.php`) — the Registration "I Want To Shop" / "I Want To Sell" selector. `light` variant (white background, black text) and `dark` variant (black background, white text) per the screenshot; both get the same **active state** (gold border + lift) when selected via JS — the "I Want To Sell" card's black background is its normal resting style, not a selected-state indicator, which the original screenshot's static mockup could be misread as.

## Navbar

Two variants:
- **Full** (Homepage, and later authenticated pages) — logo, center nav links, right-side cart/actions.
- **Minimal** (Login/Registration) — logo left, single utility link right ("Back to Home" or none).

**Implemented 2026-07-02 (Step 4) — auth-aware state:** the "full" variant's right-hand side shows "Profile" (linking to Login) for guests, or the authenticated user's name + a "Logout" button for logged-in users. The Logout control is a `<button>` inside a small `<form method="POST">` (CSRF-protected, matches the `.nav-link` visual style) rather than a link — logout is a state-changing action and shouldn't be a bare `<a>`/GET request.

**Updated 2026-07-02 (Step 6):** the username is now a real link to `/account` (was inert text). Also fixed the "Home" link's active state, previously hardcoded `true` — now `request()->routeIs('home')`.

## Account Tab Nav

**Implemented 2026-07-02 (Step 6)** (`resources/views/components/account-nav.blade.php`, styles in `resources/css/components/account.css`) — horizontal tab row (Profile / Addresses / Wishlist) used across the Buyer Module pages. Active tab: black text, gold bottom border. Same hover/transition timing as the main navbar's nav-links for visual consistency.

## Address Card

**Implemented 2026-07-02 (Step 6)** — white card, thin border, radius-sm, used in the address book. Includes a small pill "Default" badge (beige background, uppercase micro text) and inline text-button actions ("Set as Default" in gold, "Remove" in muted gray that turns error-red on hover — deliberately less visually prominent than other actions since it's destructive).

## Vendor Sidebar

**Implemented 2026-07-03 (Step 7)** (`components/vendor-sidebar.blade.php`, styles in `resources/css/pages/vendor.css`) — vertical tab nav (Dashboard / Products / Store / Profile) alongside vendor page content, distinct from the horizontal `account-nav`. Active link: white background + soft shadow (card-like), rather than account-nav's underline — different treatment because this one sits beside content in a sidebar layout, not above it in a tab strip. Collapses to a horizontally-scrollable row below 900px (same "sidebar becomes tabs" responsive pattern as account-nav, just triggered at a wider breakpoint since it carries a 2-column grid alongside it).

## Status Badge

**Implemented 2026-07-03 (Step 7)** (`components/status-badge.blade.php`, styles in `resources/css/components/data-display.css`) — small uppercase pill, color mapped by status value. Reused across three different status vocabularies (`products.status`: draft/published/archived; `vendor_profiles.approval_status`: pending/approved/rejected; `users.status`: active/inactive/suspended, not yet surfaced anywhere but ready) via one lookup table inside the component — this is the "genuinely reusable, not page-specific" component the Step 7 brief asked for.

**Extended 2026-07-03 (Step 10):** the lookup table gained `processing`/`completed`/`cancelled` (`orders.order_status`), `paid` (`orders.payment_status`, reusing the existing `pending` entry for its other state), and `shipped`/`delivered` (`orders.delivery_status`/`order_items.fulfillment_status`). Chose to extend the existing component's map rather than build a separate `order-status-badge` component (as the Step 10 brief's own component examples suggested) — the brief's list was examples of what *might* be needed, not a mandate, and this component was explicitly designed in Step 7 to absorb exactly this kind of new vocabulary.

**Note from Step 11:** `Category`'s active/archived state is deliberately rendered as plain text ("Active"/"Archived" from `$category->trashed()`) on the admin Category list, not via `<x-status-badge>` — categories have no `status` column at all (only `deleted_at`), so there's no string value to look up in the badge's vocabulary map. Not extending the component for a boolean that isn't a real status enum.

## Empty State

**Implemented 2026-07-03 (Step 7)** (`components/empty-state.blade.php`) — dashed border, title + optional message + optional action slot. First used for the vendor product list with zero products; generic enough to reuse anywhere a list can be empty (wishlist, addresses, future admin lists). **Reused as-is for the empty cart (Step 10)** rather than building a separate `empty-cart` component, per the same "genuinely reusable" reasoning as Status Badge above.

## Flash Status

**Implemented 2026-07-02 (Step 4)** (`components/flash-status.blade.php`) — a single beige banner rendering `session('status')`, used for every success/info confirmation across the app. **Extended 2026-07-03 (Step 10)** to also render Laravel's validation `$errors` bag (styled as a red error variant, `.flash-status--error`) whenever present — fixes a real gap found during Step 10 verification where a stock-limit validation failure correctly blocked the action but showed the user nothing. Since this component already sits on every major page, the fix applies everywhere at once rather than needing a per-page error block.

## Pagination

**Implemented 2026-07-03 (Step 7)** (`components/pagination.blade.php`) — deliberately minimal: Previous / "Page X of Y" / Next, no numbered page list. Matches the premium-editorial "nothing busy" aesthetic better than a full numbered pager, and is enough for the product counts realistically expected here. Takes a paginator instance directly (`:paginator="$products"`), works with any Laravel paginated collection.

## Product Table

**Implemented 2026-07-03 (Step 7)** (`components/product-table.blade.php`, styles in `resources/css/components/data-display.css`) — thumbnail + name, category, price, stock, `<x-status-badge>`, edit/delete actions. Below 700px, the table collapses to stacked label-less rows (header hidden, cells become full-width blocks) rather than horizontal scrolling — chosen because a scrolling table on mobile tends to hide the actions column, which matters more than seeing every column at once.

## Form Field Components (select / textarea / file)

**Implemented 2026-07-03 (Step 7)** (`components/select-field.blade.php`, `textarea-field.blade.php`, `file-field.blade.php`) — extend the `input-field` visual language (same label style, same `.input-field-input` border/padding/focus states) to `<select>`, `<textarea>`, and `<input type="file">`. Needed once product/store forms required more than plain text/email/password inputs. The select gets a custom dropdown-arrow background image (native `<select>` styling can't otherwise match the flat, borderless-arrow look); the file input gets a dashed border to visually distinguish "drop a file here" from a text field.

**Checkbox fixed 2026-07-03 (Step 8):** `<x-checkbox>` now renders a hidden `<input type="hidden" value="0">` before the real checkbox — see DEVELOPMENT_RULES.md for why. Purely a correctness fix, no visual change.

## Product Card / Grid

**Implemented 2026-07-03 (Step 8)** (`components/product-card.blade.php`, `product-grid.blade.php`, styles in `resources/css/components/product-display.css`) — 4-column grid on desktop (2 on tablet, 2 on mobile — kept 2 rather than 1 on mobile since the cards are simple enough to stay legible at that width). Card: square image (gradient placeholder if none uploaded, matching the homepage editorial cards' existing placeholder treatment), vendor name (micro/uppercase), product name, `<RatingStars>`, `<PriceBadge>`. Whole card is a single link to the product page — no separate "view" button.

## Product Gallery

**Implemented 2026-07-03 (Step 8)** (`components/product-gallery.blade.php`, `resources/js/gallery.js`) — square main image with thumbnails below (only rendered if a product has more than one image). Click a thumbnail to swap the main image `src` and toggle an `is-active` border — plain vanilla JS, no zoom, no carousel library, per the brief's explicit "do not over-engineer" instruction.

## Filter Sidebar

**Implemented 2026-07-03 (Step 8)** (`components/filter-sidebar.blade.php`, `resources/js/filters.js`) — fixed-width (240px) sidebar, collapses to full-width above the results on tablet/mobile. All filters are a single `<form method="GET">` submitting to the current URL, so filtered views stay bookmarkable. `<select>` filters auto-submit on `change`; price min/max need an explicit "Apply Filters" click (auto-submitting on every keystroke of a number field would be poor UX). The Category dropdown is conditionally omitted when already on a category page (passed as `null`).

## Search Bar

**Implemented 2026-07-03 (Step 8)** (`components/search-bar.blade.php`) — single input + icon button, black background matching the primary button treatment. Appears at the top of the Search page itself, not in the main navbar — kept the navbar untouched to preserve the original screenshots' exact layout rather than retrofitting a search affordance into it.

## Price Badge / Rating Stars / Breadcrumb

**Implemented 2026-07-03 (Step 8)** — three small, genuinely reusable primitives. `<x-price-badge>` formats `{currency} {amount}` consistently everywhere a price appears. `<x-rating-stars>` shows filled/outline stars based on a rounded average, or "No reviews yet" text when there's no rating data — never shows a fake/zero-star state. `<x-breadcrumb>` takes an array of `{label, href}` pairs, auto-detecting the last (current, non-linked) item.

## Recommendation Card / Section

**Implemented 2026-07-03 (Step 9)** (`components/recommendation-card.blade.php`, `recommendation-section.blade.php`, styles in `resources/css/components/recommendations.css`) — `<x-recommendation-card>` reuses the existing `product-card` visual language exactly (same image/vendor/name/rating/price markup and CSS classes) but adds one line beneath the price: the recommendation's `reason` in small gold text, and links through the click-tracking redirect (`recommendations.click`) instead of directly to the product page. `<x-recommendation-section>` wraps a title + a grid of cards + an empty-state fallback, and is the one component reused identically across all three integration points (Home "Recommended For You", Product Detail "Similar Products", the dedicated Recommendations page) — exactly the "keep all recommendation components reusable" requirement from the Step 9 brief, satisfied by having only one section component rather than three near-duplicates.

## Algorithm Switch

**Implemented 2026-07-03 (Step 9)** (`.algorithm-switch`/`.algorithm-switch-link` in `resources/css/components/recommendations.css`) — a small pill-button row on the `/recommendations` page only, letting the Product Owner compare Hybrid/Content-Based/Collaborative/Trending side-by-side via a `?algorithm=` query param. Active pill: gold background, white text — same visual "selected" language as other active states in this system (gold border/background signals selection throughout, e.g. Role Card, Account Tab Nav). Not reused elsewhere; this is a single-purpose comparison tool, not a general-purpose tab component.

## Cart Item / Cart Summary

**Implemented 2026-07-03 (Step 10)** (`components/cart-item.blade.php`, `cart-summary.blade.php`, styles in `resources/css/components/commerce.css`) — `<x-cart-item>` shows a product thumbnail, name, unit price, a small `−`/quantity/`+` control row (each a tiny same-page POST form, same "no-JS-required" pattern as every other quantity/toggle control in this app), line total, and a Remove action. Below 700px it reflows from a single row into a 2-column grid via named `grid-template-areas` (image+body, then quantity, then total+remove stacked) rather than horizontal scrolling — same reasoning as the Step 7 Product Table's mobile treatment. `<x-cart-summary>` is the shared subtotal/checkout-CTA block reused identically on the cart page and (in an extended inline form) the checkout page.

## Order Card / Order Tracking

**Implemented 2026-07-03 (Step 10)** (`components/order-card.blade.php`, `.order-tracking`/`.order-tracking-step` in `resources/css/pages/commerce.css`) — `<x-order-card>` is the "My Orders" list-row: order number, date, all three status badges, item count, total, whole card clickable through to the order detail. Order Tracking is a 3-step horizontal stepper (Order Placed → Shipped → Delivered) driven entirely by `orders.delivery_status` — completed steps get a gold dot and connector line, matching the gold-signals-progress/selection convention used throughout (Algorithm Switch, Role Card, Account Tab Nav). Deliberately not a generic stepper component — it's specific to the 3 fixed delivery states this system has, not a reusable N-step primitive.

## Admin Sidebar

**Implemented 2026-07-03 (Step 11)** (`components/admin-sidebar.blade.php`, styles in `resources/css/pages/admin.css`) — a vertical tab nav (Dashboard / Users / Vendors / Products / Categories / Reports / Recommendation Analytics / Audit Logs / Settings / System Health) reusing the exact same active-link treatment (white background + soft shadow) and responsive horizontal-scroll-below-900px behavior as the Step 7 Vendor Sidebar, rather than inventing a new sidebar visual language for the admin area — the two sidebars are visually identical, differing only in their link list.

## Bar Chart

**Implemented 2026-07-03 (Step 11)** (`components/bar-chart.blade.php`, styles in `resources/css/pages/admin.css`) — hand-written CSS bars (a fixed-height flex row of columns, each bar's height set via an inline `--bar-height` custom property computed server-side as a percentage of the series' max value), not a charting library — matches this project's established "no unnecessary dependencies, vanilla only" rule. Used for all 6 Dashboard charts (Orders/Revenue/New Users/Vendor Registrations per month, Recommendation Clicks per month, Best Selling Categories). Each bar's value/label sits in its own flex-shrink-0 slot outside the percentage-height bar track, so the tallest bar in a series always reaches 100% of the chart's available height regardless of how long its label text is — a real bug (bars topping out around two-thirds height) was found and fixed during this step's own verification, see PROJECT_DECISIONS.md.

## Dashboard Summary Card / Notification Center

**Implemented 2026-07-03 (Step 11)** (`.admin-summary-grid`/`.admin-summary-card`, `.admin-notifications`/`.admin-notification` in `resources/css/pages/admin.css`) — summary cards reuse the exact same visual shape as the Step 7 Vendor Dashboard's stat cards (white surface, large serif value, small muted label), just in a wider 6-column grid. The Notification Center is a stack of beige pill rows, each only rendered when its count is non-zero (an admin with a fully healthy system sees no notification list at all, not a list of "0" rows) — paired with 🟢/🟡/🔴 emoji badges (no custom icon needed) for the Dashboard's and Health Dashboard's system-status indicator.

## Footer

Not yet visible in the supplied screenshots — will be specified once a page that shows it is approved (Level 2).

## Badges

Small pill/rounded rect, used for cart item count. Champagne Gold or Luxury Black background, small numeral, high contrast text.

## Product Cards

Not yet needed (Phase 4). Reserved token names only for now so future components stay in the same spacing/color system.

## Toast Notifications, Loading Indicators, Pagination, Modal, Breadcrumb

Not present in the three approved screenshots — will be designed to match this system's spacing/color/animation rules when a page first requires them, and proposed for review before use (Level 2).

## Icons

Thin-line, single-weight icon set (shopping bag, storefront, mail, eye/visibility, checkmark, arrow, person-plus, hanger, badge-check, shield, truck) rendered in either Gold or Black depending on surface.

**Resolved 2026-07-02:** implemented as hand-written inline SVGs via a single `<x-icon name="...">` Blade component (`resources/views/components/icon.blade.php`), not a third-party icon library/package — keeps the stack dependency-free (no npm icon package), matches the "no jQuery/React/Vue, vanilla only" constraint, and keeps icon styling driven entirely by our own CSS custom properties (`currentColor` stroke, sized via CSS per usage context) rather than a library's defaults.

Implemented: `arrow-right`, `hanger`, `badge-check`, `shield`, `truck` (Step 1); `mail`, `eye`, `eye-off`, `person-plus` (Step 2); `shopping-bag`, `storefront` (Step 3); `search`, `heart` (Step 8). Full icon set from the original plan, plus catalogue additions, is now covered.

## Animation System

| Element | Duration | Easing | Properties |
|---|---|---|---|
| Buttons | 200ms | ease-in-out | opacity, transform, background-color |
| Cards | 250ms | ease-in-out | transform, box-shadow |
| Accordion | 350ms | ease-in-out | height, opacity, transform |

Rule of thumb: everything subtle, nothing flashy. No bouncing, no spinning, no attention-grabbing motion — this is a premium/editorial product, not a consumer growth app.

## Future Improvements

Ideas surfaced during planning that are intentionally postponed, not forgotten:

- Toast Notifications, Loading Indicators, Pagination, Modal, Breadcrumb components — deferred until a page actually needs one (none of the three approved screenshots show them).
- Product Card component — reserved token names only; real spec waits for Phase 4.
- Footer component — not visible in any approved screenshot yet.
- Dark-mode token variants — not requested, would need contrast-checking against the current palette if ever wanted.
- Real product photography to replace the CSS gradient placeholders on the homepage editorial cards.
- Motion-reduced (`prefers-reduced-motion`) fallback — **implemented 2026-07-02** in `resources/css/base.css` (global media query zeroes out animation/transition duration); noting here since this list previously flagged it as outstanding.
- Address card has no edit-in-place — only add/remove/set-default. Editing an existing address's line1/city currently means removing and re-adding it. Worth a proper edit form once the Buyer Module gets more use.
- Pagination component has no numbered page list — fine at current scale, may need one if a vendor ever has hundreds of products.
- Vendor sidebar's mobile horizontal-scroll has no fade/arrow affordance hinting more tabs exist off-screen.
- Status badge's color map is hardcoded inside the component rather than defined as design tokens (e.g. `--color-status-success`) — would be worth promoting to `variables.css` if more status vocabularies get added later.
- Recommendation reason text is a single style (small, gold) regardless of which algorithm produced it — a future pass could differentiate visually (e.g. a small icon per algorithm source) if that's ever wanted for the academic-comparison use case specifically.
- Algorithm Switch has no visual indicator of *why* an algorithm produced fewer/no results (e.g. "no collaborative signal yet") beyond the shared empty-state message — could be worth a more specific empty-state copy per algorithm if this becomes a frequently-used comparison tool.
- **Toast Notifications — still not built.** `<x-flash-status>` remains a static banner at the top of the page, not a dismissible/auto-hiding toast — fine at this scale, but the Step 10 error-message extension makes this component do more work than a "toast" placeholder name originally implied.
- Order Tracking's 3-step stepper is hardcoded to the 3 delivery states this system has (`pending`/`shipped`/`delivered`) — would need generalizing if a more granular fulfillment pipeline (e.g. "processing", "out for delivery") is ever wanted.
- Cart Item's quantity control has no direct-input option — only +/− buttons, no typing a quantity directly. Fine for small quantities, would be worth adding for a catalogue with bulk-purchase use cases.
- Bar Chart has no tooltip/hover detail — value and label are always visible as static text beneath each bar, which is enough at the current small-dataset scale but would get cramped with longer category names or more than ~6 bars in a series.
- Admin Sidebar's link list (10 items) is the longest of any sidebar in the app — worth watching whether it needs grouping/collapsing if a future step adds more admin sections.
