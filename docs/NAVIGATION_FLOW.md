# NAVIGATION_FLOW.md

Sitemap, routing conventions, and redirect rules across all roles. Planning document — routes below beyond Phase 3 are **proposed structure**, not implemented; only guest routes exist today.

Status: **Guest routing, real authentication (Step 4), the Buyer Module (Step 6), the Vendor Module (Step 7), the Product Catalogue (Step 8), the Recommendation Engine (Step 9), the Shopping Workflow & Order Management (Step 10), the Admin area (Step 11), and Forgot/Reset Password (Phase 11.5) implemented. No route group remains proposed-only.**
Last updated: 2026-07-04

---

## URL Conventions (proposed, Level 1 unless flagged)

- Kebab-case slugs for all human-readable URL segments (`/men/outerwear`, not `/men/Outerwear`).
- Resourceful routing where it fits Laravel convention (`/products/{slug}`, not `/product-details/{id}`).
- Role-scoped route groups/prefixes for non-guest areas once they exist: `/vendor/...`, `/admin/...` (buyer area is unprefixed — it *is* the main site).
- Named routes throughout (`route('login')`, not hardcoded paths) — Level 1, standard Laravel practice.

---

## Guest Sitemap (Phase 3 — active scope)

| Route | Page | Middleware |
|---|---|---|
| `/` | Homepage | none |
| `/login` (GET, POST) | Login — ✅ real authentication (Step 4) (`login`, `login.store`) | `guest` (bounces authenticated users to Home) |
| `/register` (GET, POST) | Registration — ✅ real account creation (Step 4) (`register`, `register.store`) | `guest` |
| `/logout` (POST) | Ends the session (`logout`) — **new in Step 4** | `auth` |
| `/coming-soon` | Placeholder for nav items without a real page yet — **new in Step 4** (`coming-soon`) | none |
| `/password/forgot` (GET, POST) | Forgot Password — ✅ real reset-link request (Phase 11.5) (`password.request`, `password.email`) | `guest` |
| `/password/reset/{token}` (GET) / `/password/reset` (POST) | Reset Password — ✅ real password reset via emailed token (Phase 11.5) (`password.reset`, `password.update`) | `guest` |

Note: the route is named `password.request` rather than the `/password/reset` path originally sketched here — matches Laravel's own naming convention for the "request a reset link" form, distinct from `password.reset` (the form that handles the emailed token, implemented in Phase 11.5 using Laravel's built-in Password broker).

Everything else a guest can *see links to* (Men, Women, Accessories, Recommendations, Cart) resolves via a `gated_route()` helper (`app/helpers.php`) — `/login` for guests, `/coming-soon` for authenticated users. These are not separate pages yet; they're gated destinations either way.

## Redirect Rule: Guest Gate — ✅ implemented (Step 4)

```
Guest clicks any gated nav item or CTA
        │
        ▼
gated_route() resolves to /login (auth()->check() is false)
        │
        ▼
User submits valid credentials → LoginRequest::authenticate() (Auth::attempt, rate-limited)
        │
        ▼
Session regenerated, redirect()->intended(route('home'))
```

Example:
```
Guest → clicks "Men" → gated_route() → /login (nothing "intended" is stored today, since Men has no real
route yet — intended() falls back to home once Phase 4/buyer-discovery gives Men a real destination)
Login success → redirect to Home
```

**Authenticated nav behavior (new in Step 4):** the exact same nav items resolve to `/coming-soon` instead once `auth()->check()` is true — avoids the broken UX of an already-logged-in user being sent to the login form (which the `guest` middleware would just bounce back to Home anyway, but silently, which reads as a dead link). `/coming-soon` gives an honest "this isn't built yet" message instead.

**Updated Step 6:** the navbar's authenticated-state username (previously plain text, no destination) is now a real link to `route('account.edit')` — the entry point to the Buyer Module. Also fixed in passing: the "Home" nav-link's active state was hardcoded `true` regardless of the current page, harmless while Home was the only real authenticated page but incorrect now that `/account`/`/wishlist` exist — now uses `request()->routeIs('home')`.

**Updated Step 8:** `gated_route()` now takes an optional real destination — `gated_route(route('categories.show', 'men'))` etc. — used for authenticated users. Guests still always go to `/login` regardless of what's passed, preserving the guest-gate concept exactly as designed; the parameter only changes where an *authenticated* user ends up. Men/Women/Accessories, "Explore Collection," and the homepage's three editorial cards now resolve to real pages (categories/catalogue) for logged-in users. The Cart nav item's `(N)` count is now real (`cart_count()` helper reading the session), though the link itself still goes to `/coming-soon` since there's no cart page yet (Step 10).

**Updated Step 9:** "Recommendations" and "Get Outfit Suggestions" now resolve to the real `/recommendations` page (`gated_route(route('recommendations.index'))`) for authenticated users — the last two nav items that still pointed at `/coming-soon` since Step 4. `/coming-soon` is now reached only by the Cart link (Step 10 territory).

**Updated Step 10:** the Cart nav item now resolves to the real `/cart` page (`gated_route(route('cart.index'))`) — the last nav item that still pointed at `/coming-soon`. `/coming-soon` is no longer reached from anywhere in the main nav; it remains registered only as a generic fallback for any future nav item added ahead of its backing page. A new "My Orders" nav item was added, always resolving to `route('orders.index')` for authenticated users (not gated — it's not aspirational, the page fully exists).

**Updated Step 11:** a new "Admin" nav item appears only for `auth()->user()->role === 'admin'` (alongside the existing vendor-only "Dashboard" link), always resolving to `route('admin.dashboard')` — not gated, since the page fully exists and the `admin` middleware handles authorization.

## Registration → Login Flow — ✅ implemented (Step 4)

**Auto-login after signup** was chosen (a Level 2 judgment call, not asked upfront — see PROJECT_DECISIONS.md): both buyer and vendor registration create a real `users` row and log the new user in immediately, redirecting to Home with a welcome flash message. No separate "please sign in" step.

```
/register
   │
   ├─ click "I Want To Shop" → buyer accordion expands → submit
   │        │
   │        ▼
   │  RegisterRequest validates → User::create() (name, email, phone, role=buyer, password)
   │  Auth::login($user) → session regenerated → redirect to Home with "Welcome to R&C Fashion, {name}!"
   │
   └─ click "I Want To Sell" → vendor accordion expands → submit
            │
            ▼
      Same as buyer, EXCEPT: store_name is validated (required) but NOT persisted — no vendor_profiles
      table exists yet. Vendor gets role=vendor on the same users row, no approval gate (approval_status
      has no schema home either) — vendors have full access immediately, same as buyers. This remains an
      open Level 3 question, see DATABASE_BLUEPRINT.md's Decision Fork.
```

---

## Post-Login Sitemap (proposed structure, future phases — NOT built yet)

**Current interim state (Step 4):** all authenticated users land on the same Home page regardless of role, and all "not built yet" nav items resolve to the shared `/coming-soon` stub. Role-based landing pages below are still proposed, not implemented.

Role-based landing page after successful login:

| Role | Landing page (proposed) | Phase |
|---|---|---|
| Buyer | Discovery feed (personalized recommendations) — replaces a generic homepage per the brief's "Buyer Discovery" principle | 4 |
| Vendor | Vendor dashboard | 5 |
| Admin | Admin dashboard | 6 |

### Buyer area (account/wishlist Step 6; catalogue Step 8; cart/checkout/orders Step 10)

```
/                          → homepage — still the same static hero for guest and authenticated alike (no discovery-feed personalization; that's Step 9)
/products                  → ✅ catalogue homepage (Step 8) — latest + featured + filters + pagination (products.index)
/products/{product:slug}   → ✅ product detail (Step 8) — gallery, specs, wishlist toggle, add to cart, vendor link (products.show)
/categories/{category:slug} → ✅ category browsing (Step 8) — same as index, pre-scoped, no category filter shown (categories.show)
/search?q=...              → ✅ search by name/description (Step 8) — plain LIKE query, no ranking/AI (search.index)
/vendors/{vendor:store_slug} → ✅ minimal public vendor storefront (Step 8) — logo, description, product grid (vendors.show)
/recommendations           → ✅ dedicated recommendations hub (Step 9) — hybrid blend + algorithm switcher for comparison (recommendations.index)
/recommendations/click/{product} → ✅ click-tracking redirect (Step 9) — logs the click, marks the log row, redirects to the real product page (recommendations.click)
/account                   → ✅ profile view/edit (Step 6) — GET/PUT, `auth` middleware
/account/addresses         → ✅ address book: list, add, delete, set default (Step 6) — ownership enforced via AddressPolicy
/wishlist                  → ✅ wishlist view (Step 6) + ✅ add/remove (Step 8, `wishlist.store`/`wishlist.destroy`) — no longer empty-only
/cart                      → ✅ persistent database cart view (Step 10) — cart.index
/cart/{product}            → ✅ add to cart (Step 10, replaces Step 8's session version) — cart.store
/cart/items/{cartItem}     → ✅ update exact quantity (Step 10) — cart.update
/cart/items/{cartItem}/increase → ✅ increase quantity by 1 (Step 10) — cart.increase
/cart/items/{cartItem}/decrease → ✅ decrease quantity by 1 (Step 10) — cart.decrease
/cart/items/{cartItem}     → ✅ remove item (DELETE, Step 10) — cart.destroy
/cart                      → ✅ clear cart (DELETE, Step 10) — cart.clear
/checkout                  → ✅ shipping address + delivery option + payment method + order summary (Step 10) — checkout.index / checkout.store
/orders                    → ✅ buyer order history (Step 10) — orders.index
/orders/{order}            → ✅ order details + tracking stepper — doubles as the post-checkout confirmation page (Step 10) — orders.show
```

**Men/Women/Accessories are gone from this list as separate routes** — they resolve to `/categories/men`, `/categories/women`, `/categories/accessories` (the seeded category slugs), not distinct top-level routes. The navbar links to these directly by slug rather than querying the Category model in the view.

### Vendor area (implemented Step 7, 2026-07-03; extended Step 10)

```
/vendor                              → ✅ dashboard (vendor.dashboard) — real stat counts + quick actions
/vendor/products                     → ✅ inventory list, paginated (vendor.products.index)
/vendor/products/create              → ✅ (vendor.products.create / .store)
/vendor/products/{product}/edit      → ✅ (vendor.products.edit / .update)
/vendor/products/{product}           → ✅ delete (vendor.products.destroy)
/vendor/products/{product}/images/{image} → ✅ delete (vendor.products.images.destroy)
/vendor/orders                       → ✅ incoming order items, paginated (Step 10) — vendor.orders.index
/vendor/orders/{order}                → ✅ order detail, scoped to only this vendor's items within the order (Step 10) — vendor.orders.show
/vendor/orders/items/{orderItem}/fulfillment → ✅ update fulfillment status (Step 10) — vendor.orders.fulfillment
/vendor/store                        → ✅ store profile + logo (vendor.store.edit / .update)
/vendor/analytics                    → not built — only basic dashboard counts exist, no dedicated analytics page (Phase 9 scope)
```

All routes above require both `auth` and a new `vendor` middleware (checks `users.role === 'vendor'`, aborts 403 otherwise) — see DEVELOPMENT_RULES.md for the Policy-vs-middleware distinction. Verified live: a buyer account hitting `/vendor` gets 403 (re-confirmed against `/vendor/orders` specifically in Step 10); a second vendor account cannot view/edit/delete the first vendor's products (403 each, `ProductPolicy`/`StorePolicy`); a vendor can only update fulfillment on their own order items within a shared order (`OrderPolicy::updateFulfillment`).

### Admin area (implemented Step 11, 2026-07-03)

```
/admin                                    → ✅ dashboard (admin.dashboard) — 6 summary cards, notification center, 6 charts
/admin/users                              → ✅ list/search/filter (admin.users.index)
/admin/users/{user}/suspend               → ✅ POST, requires no body beyond an optional reason (admin.users.suspend)
/admin/users/{user}/activate              → ✅ POST (admin.users.activate)
/admin/users/{user}/assign-admin          → ✅ POST (admin.users.assign-admin)
/admin/vendors                            → ✅ list/search/filter by approval status (admin.vendors.index)
/admin/vendors/{vendor}                   → ✅ detail + statistics (admin.vendors.show)
/admin/vendors/{vendor}/approve           → ✅ POST, reason required (admin.vendors.approve)
/admin/vendors/{vendor}/reject            → ✅ POST, reason required (admin.vendors.reject)
/admin/vendors/{vendor}/suspend           → ✅ POST, reason required (admin.vendors.suspend)
/admin/vendors/{vendor}/restore           → ✅ POST, reason required (admin.vendors.restore)
/admin/products                           → ✅ moderation list/search/filter by status (admin.products.index)
/admin/products/{product}/approve         → ✅ POST, reason required (admin.products.approve)
/admin/products/{product}/reject          → ✅ POST, reason required (admin.products.reject)
/admin/products/{product}/hide            → ✅ POST, reason required (admin.products.hide)
/admin/products/{product}/archive         → ✅ POST, reason required (admin.products.archive)
/admin/products/{product}/restore         → ✅ POST, reason required (admin.products.restore)
/admin/categories                         → ✅ list (includes soft-deleted rows) (admin.categories.index)
/admin/categories/create                  → ✅ (admin.categories.create / .store)
/admin/categories/{category}/edit         → ✅ (admin.categories.edit / .update)
/admin/categories/{category}              → ✅ archive — soft delete (DELETE, admin.categories.destroy)
/admin/categories/{category}/restore      → ✅ POST, resolves a trashed category by id (admin.categories.restore)
/admin/reports                            → ✅ 8 report types + date-range filter (admin.reports.index)
/admin/reports/export                     → ✅ CSV download, same filters as the current report (admin.reports.export)
/admin/recommendation-analytics           → ✅ read-only (admin.recommendation-analytics.index)
/admin/audit-logs                         → ✅ filterable list (admin.audit-logs.index)
/admin/settings                           → ✅ (admin.settings.edit / .update)
/admin/health                             → ✅ read-only (admin.health.index)
```

All routes above require both `auth` and the new `admin` middleware, which checks **both** `users.role === 'admin'` and `users.status === 'active'` — a suspended administrator is locked out exactly like a suspended buyer/vendor, aborting 403 rather than silently degrading. Verified live: a buyer account hitting any `/admin/*` route gets 403.

**Vendor approval queue** — resolves the "ties to `vendor_profiles.approval_status`" note this section used to carry as a proposal: `/admin/vendors` filters by `approval_status`, and Approve/Reject/Suspend/Restore all write to it (or, for suspend/restore, to the vendor's own `users.status` — see DATABASE_BLUEPRINT.md's Administration section for why).

---

## Breadcrumb Conventions

Not present in any of the three approved screenshots. Proposed only for pages that need wayfinding depth ≥ 2 (e.g. `Home / Men / Outerwear / Product Name`) — will be specced in UI_BLUEPRINT.md once Phase 4 category/product pages are in scope.

---

## Future Improvements

- Deep-linking / query-param conventions for filters (`/men?color=black&size=M`) — Phase 4 concern, not decided.
- Breadcrumb component spec — deferred until a page needs it (see above).
- **Vendor storefront public pages — implemented Step 8** (`/vendors/{store_slug}`), resolving this exact future-improvement note. Still intentionally minimal — no vendor-specific filters/sub-nav, see UI_BLUEPRINT.md.
- SEO-friendly route/meta conventions — not mentioned in the brief; worth a short discussion once buyer-facing pages exist.
- Email verification is not implemented — `email_verified_at` exists on `users` but nothing sets it. Worth deciding whether this project needs it at all (many student/demo projects skip it).
- **`/coming-soon` — now unreachable from any nav item (Step 10)**, resolving the note that used to sit here. Every nav item that used to point at it (Men/Women/Accessories/Explore Collection in Step 8, Recommendations/Get Outfit Suggestions in Step 9, Cart in Step 10) now resolves to a real page. The route/view stay registered as a generic fallback for any future nav item added ahead of its backing page, but nothing currently links to it.
- **Recommendations page — implemented Step 9** (`/recommendations`, `/recommendations/click/{product}`), resolving the note that used to sit here.
- Vendor approval workflow (`vendor_profiles.approval_status`) remains unbuilt — vendors currently get full access immediately upon registration. Revisit once vendor_profiles exists.
- No profile photo upload UI — `users.profile_photo` column exists but nothing sets it. Skipped in Step 6 as genuinely separate scope (file storage/validation), not core to "Buyer Module."
- **Wishlist "add" — implemented Step 8** (`wishlist.store`/`wishlist.destroy`), resolving this exact note.
- **`/cart` — implemented Step 10** (persistent database cart, full view/edit), resolving the note that used to sit here.
- Search is a plain `LIKE` match on name/description — no relevance ranking, no typo tolerance, no full-text index. Fine for the current catalogue size; would need real search infrastructure (or at minimum a MySQL FULLTEXT index) at scale.
- No `/admin` routes exist for refunds or dispute handling — still explicitly out of scope (no payment gateway exists to dispute), see PROJECT_DECISIONS.md. Vendor order *oversight* itself is not a separate admin route — administrators supervise vendors via `/admin/vendors`, not by directly viewing order lists (out of Step 11's explicit scope).
- Checkout has no address-book integration beyond pre-filling from the buyer's default `Address` — there's no "choose a saved address" selector, just one auto-filled form. Worth revisiting if buyers accumulate multiple addresses and want to pick between them at checkout.
- **Admin area — implemented Step 11** (full `/admin/*` route map above), resolving the note that used to sit here. No route group in this document remains purely proposed.
- No "revoke admin" route exists — `/admin/users/{user}/assign-admin` is one-directional by design (see PROJECT_EVOLUTION.md).
