# DATABASE_BLUEPRINT.md

**⚠️ Planning document only.** Database schema is a **Level 3 decision** (see [MASTER_BLUEPRINT.md](MASTER_BLUEPRINT.md) §8) — nothing here is final, and no migration, model, factory, or seeder gets written from this document until it's explicitly reviewed and approved, entity by entity. This is a proposal to react to, not a spec to implement.

Status: **Core catalog/vendor schema implemented (Step 5), actively used by real CRUD (Step 7), customer-facing browsing (Step 8), the recommendation engine (Step 9), the full shopping workflow (Step 10), and Administration & Management (Step 11)** — `users`, `vendor_profiles` (extended Step 7 with `phone`/`email`/`county`), `categories` (extended Step 11 with `SoftDeletes`), `products` (extended Step 8 with `is_featured`/`sizes`), `product_images`, `collections` (still unpopulated — no curation UI yet), `collection_items`, `wishlists`, `addresses`, `reviews` (schema only — nothing writes reviews yet), `user_interactions`/`recommendation_logs` (Step 9), `carts`/`cart_items`/`orders`/`order_items` (Step 10 — replaced Step 8's session-only cart), `audit_logs`/`settings` (Step 11). **`user_preferences`/`trending_scores`/`payments`/`disputes` were deliberately never built** — see the relevant sections below for why.
Last updated: 2026-07-03

---

## Purpose

Sketch the entities and relationships the platform will eventually need, so that:
- Phase 3 (guest auth) doesn't build a `users` table that has to be reshaped in Phase 5 (vendors) or Phase 7 (recommendations).
- The recommendation engine's data needs (interaction logs, preference profiles) are considered from day one, per the brief's "every product interaction feeds recommendations" principle — without actually building any of it yet.

Nothing below is scoped to a specific phase's *implementation* — it's the full map, so early decisions don't paint later phases into a corner. Actual migrations get written phase-by-phase, only for the entities that phase needs, only after this document (or the relevant slice of it) is approved.

---

## Entity Overview (draft)

### Identity & Roles

**`users`** — ✅ **Implemented (Step 1A, 2026-07-02)**
Core account table for every person on the platform — buyer, vendor, or admin.
- `id`, `name`, `email` (unique), `email_verified_at`, `password`, `role` (enum: `buyer` / `vendor` / `admin`, default `buyer`), `phone` (nullable), `address` (nullable text), `profile_photo` (nullable string), `status` (enum: `active` / `inactive` / `suspended`, default `active`), `remember_token`, timestamps.
- Migration: `database/migrations/0001_01_01_000000_create_users_table.php` (modified from Laravel's default, not a separate migration).

**`vendor_profiles`** — ✅ **Implemented (Step 5, 2026-07-02; extended Step 7, 2026-07-03)**
Vendor-specific fields that don't belong on every user.
- `id`, `user_id` (FK → users, unique — 1:1), `store_name`, `store_slug` (unique, auto-generated from `store_name` with a numeric-suffix collision fallback), `description` (nullable), `logo_path` (nullable), `approval_status` (enum: `pending` / `approved` / `rejected`, default `pending`), **`phone`, `email`, `county`** (all nullable, added Step 7 for the Store Profile page), timestamps.
- Migrations: `2026_07_02_200349_create_vendor_profiles_table.php` (Step 5), `2026_07_03_054532_add_contact_fields_to_vendor_profiles_table.php` (Step 7 — `phone`/`email`/`county`). Model: `App\Models\VendorProfile`, `hasOne` from `User::vendorProfile()`.
- **`RegisterController` now creates this row** for vendor signups (wrapped in a DB transaction with the `User::create()` call) — closes the gap flagged in Steps 3/4 where `store_name` was validated but discarded.
- **`approval_status` defaults to `pending` but still nothing enforces it as of Step 7** — vendors get full immediate access to the Vendor Module (dashboard, product CRUD, store editing) the moment they register, same as before. Step 7 was explicitly scoped to vendor-facing management (per its own brief), not admin approval workflows — actually gating vendor actions behind `approved` status remains Phase 6 (Admin) territory. The dashboard *displays* the current `approval_status` as a badge, which is new, but display ≠ enforcement.
- **`county`** validated against a hardcoded list of Kenya's 47 counties (`config/kenya.php`), not free text — avoids typos/inconsistent location data at the one place it's collected.

*Decision Fork #1 (single `users` table + role enum vs. separate per-role tables): fully resolved now.* `role`/`phone`/`address`/`profile_photo`/`status` on `users` (Step 1A) + a separate `vendor_profiles` side table for vendor-only fields (Step 5) — exactly the originally-recommended shape.

**⚠️ Discovered overlap (Step 5):** `users.address` (a single nullable text column, from Step 1A's exact field list) and the new `addresses` table (structured, multiple addresses per user, with `is_default`) now both exist and both plausibly represent "a user's address." Nothing currently reads or writes `users.address` — it's unused dead weight now that `addresses` exists. **Not resolved here** — this is exactly a Level 3 "removing a feature" question. Options for a future pass: (a) drop `users.address` in favor of `addresses` exclusively, (b) keep `users.address` as a denormalized "primary address" cache synced from the default `addresses` row, (c) leave both and let call sites pick. Flagging rather than deciding.

### Catalog — ✅ Implemented (Step 5, 2026-07-02)

**`categories`** — ✅ **extended Step 11, 2026-07-03: the project's only soft-deleted model**
Hierarchical — supports the Men / Women / Accessories top level plus subcategories. Seeded with Men/Women/Accessories as top-level rows (`database/seeders/CategorySeeder.php`, matching the nav already built in Step 1).
- `id`, `name`, `slug`, `parent_id` (nullable, self-referencing FK, `nullOnDelete`), `display_order`, timestamps, **`deleted_at`** (added Step 11).
- **Why soft deletes here and nowhere else:** `products.category_id` uses `->constrained()->cascadeOnDelete()` — a hard category delete would silently destroy every product in it. No route exposed category deletion before Step 11's admin Category Management, so this was a latent risk this step is the first to actually surface. `User`/`Product`/`VendorProfile` deliberately do **not** get `SoftDeletes` — the trait's global scope would change query results everywhere those models are already used across every frozen module, an out-of-proportion blast radius for a feature none of those modules asked for.
- `Admin\CategoryService::archive()` additionally guards against archiving a category that still has products or child categories (throws a `ValidationException`) — this is what actually makes the soft delete safe, not the `deleted_at` column alone.

**`products`** — ✅ **now populated by real vendor CRUD (Step 7) and browsable via the catalogue (Step 8)**
- `id`, `vendor_id` (FK → users, `cascadeOnDelete`), `category_id` (FK, `cascadeOnDelete`), `name`, `slug` (unique, auto-generated with numeric-suffix collision fallback, same pattern as `vendor_profiles.store_slug`), `description`, `price` (decimal 10,2), `currency` (default `KES`), `stock_quantity`, `status` (enum: `draft` / `published` / `archived`, default `draft`), `primary_color`, **`is_featured`** (boolean, default false, added Step 8), **`sizes`** (JSON nullable, added Step 8), timestamps. **Decision Fork #2 (product variants) resolved: flat, no variants** — matches the doc's original recommendation, and Step 8's `sizes` field deliberately doesn't reopen this (see below).
- Vendors manage only their own products (`Vendor\ProductController`, ownership enforced via `ProductPolicy`) — verified live with a real cross-vendor 403 test.
- **Customer-facing visibility rule (Step 8):** the catalogue only ever queries `status = published` (via a `Product::scopePublished()` local scope). Draft/archived products 404 on `/products/{slug}` and never appear in listings/search — verified live.
- **`is_featured` and `sizes` — why these were added (Level 3, but explicitly permitted by the Step 8 brief's "add migrations if absolutely necessary... explain why"):**
  - `is_featured`: the brief requires a "Featured" section on the catalogue homepage. `collections`/`collection_items` (built Step 5) could represent this, but nothing populates them yet (no admin/vendor collection-curation UI exists), which would make a collections-based Featured section permanently empty. A vendor-settable boolean is the minimal way to make the section actually work today.
  - `sizes`: the brief requires displaying/filtering "available sizes," and nothing represents this at all (Decision Fork #2 deliberately kept the table flat). Rather than building real per-size stock tracking (a much bigger feature), added a display/filter-only JSON tag list. No per-size stock — a product's `stock_quantity` is still a single total.
  - **Not changed:** "available colours" reuses the existing single `primary_color` field as-is rather than also becoming a JSON array like `sizes` — avoids a second schema change where the brief's requirement is already mostly satisfied by what exists.

**`product_images`** — ✅ **now populated by real uploads (Step 7)**
- `id`, `product_id` (FK, `cascadeOnDelete`), `image_path`, `display_order`, timestamps.
- Files stored under `storage/app/public/products` via Laravel's `public` disk (`storage:link` symlink). `ProductImage::url` accessor wraps `Storage::disk('public')->url()`.

**`collections`**
Curated groupings — "Recommended Collections" module, outfit sets, editorial picks.
- `id`, `title`, `description`, `curated_by` (enum: `admin` / `vendor` / `system`, default `admin`), `type` (enum: `outfit` / `editorial` / `seasonal`, default `editorial`), timestamps.

**`collection_items`**
- `id`, `collection_id` (FK, `cascadeOnDelete`), `product_id` (FK, `cascadeOnDelete`), `display_order`, timestamps. Modeled as a first-class `CollectionItem` resource (not a bare Eloquent pivot) since `display_order` makes it more than a plain join table.

**`wishlists`**
- `id`, `user_id` (FK, `cascadeOnDelete`), `product_id` (FK, `cascadeOnDelete`), timestamps, **unique on `(user_id, product_id)`** — a user can't wishlist the same product twice.

**`addresses`**
- `id`, `user_id` (FK, `cascadeOnDelete`), `label` (nullable), `line1`, `city`, `phone` (nullable), `is_default` (boolean, default false), timestamps. See the `users.address` overlap note above.

**`reviews`**
- `id`, `product_id` (FK, `cascadeOnDelete`), `user_id` (FK, `cascadeOnDelete`), `rating` (unsigned tinyint — 1–5 range enforced at the application layer, not a DB constraint, once review-writing UI exists), `comment` (nullable), timestamps, **unique on `(product_id, user_id)`** — one review per user per product.

### Commerce — ✅ Implemented (Step 10, 2026-07-03) — schema smaller than the original proposal

**Step 8's session-based "Add to Cart" is fully retired.** `cart_count()` and every cart action now read/write the tables below, not the session.

**`carts`** — ✅ **Implemented.**
- `id`, `user_id` (FK, `cascadeOnDelete`, **unique** — enforces exactly one cart per user at the DB level, not just in application code), timestamps.
- Migration: `2026_07_03_130844_create_carts_table.php`. Model: `App\Models\Cart`, `hasMany` `CartItem`.

**`cart_items`** — ✅ **Implemented, deliberately no price snapshot** (unlike the original proposal's `unit_price_snapshot`).
- `id`, `cart_id` (FK, `cascadeOnDelete`), `product_id` (FK, `cascadeOnDelete`), `quantity`, timestamps. **Unique on `(cart_id, product_id)`** — adding an already-cart product increases its quantity instead of creating a duplicate row.
- **Why no price snapshot:** an active cart must always reflect the product's *current* price and stock (Phase C/D's explicit requirement) — a snapshot would either need constant re-syncing (defeating the point of a snapshot) or show stale prices. `CartItem::lineTotal` is a computed accessor (`quantity × live product price`), never stored. Price snapshotting only makes sense once a cart becomes an *order* — see `order_items` below.
- Migration: `2026_07_03_130845_create_cart_items_table.php`. Model: `App\Models\CartItem`.

**`orders`** — ✅ **Implemented**, with 3 independent status fields rather than 1, per the Step 10 brief's explicit requirement.
- `id`, `user_id` (FK, `cascadeOnDelete`), `order_number` (unique, e.g. `RC-20260703-A5M2JR`), shipping address **snapshot** (`shipping_name`/`shipping_line1`/`shipping_city`/`shipping_phone` — copied at checkout, not a live FK to `addresses`, so the order stays accurate even if the address is later edited/deleted), `delivery_option`, `payment_method`, `subtotal`/`tax`/`shipping_cost`/`total` (all decimal 10,2), `order_status`/`payment_status`/`delivery_status` (independent string fields, default `pending`/`pending`/`pending`), timestamps.
- **`order_status`/`delivery_status` are derived, not independently writable anywhere** — `OrderService::syncOrderDeliveryStatus()` recomputes both from the aggregate of the order's items' `fulfillment_status` every time a vendor updates one. See PROJECT_DECISIONS.md for the reasoning.
- Migration: `2026_07_03_130939_create_orders_table.php`. Model: `App\Models\Order`, `hasMany` `OrderItem`.

**`order_items`** — ✅ **Implemented, with a price/name snapshot** (unlike `cart_items`, this is exactly where snapshotting belongs — an order is a historical record, not a live view).
- `id`, `order_id` (FK, `cascadeOnDelete`), `product_id` (FK, **nullable**, `nullOnDelete` — the product may be deleted after the order is placed; `product_name`/`unit_price` on this row remain the source of truth for display regardless), `vendor_id` (FK → `users`, `cascadeOnDelete` — denormalized exactly as the original proposal suggested, "for multi-vendor order splitting" purposes, though Step 10 explicitly does NOT split orders into per-vendor sub-orders — the denormalization is used instead to scope what each vendor can see/manage within one shared order), `product_name`, `unit_price`, `quantity`, `fulfillment_status` (string, default `pending`), timestamps.
- Migration: `2026_07_03_130940_create_order_items_table.php`. Model: `App\Models\OrderItem`, `lineTotal` computed accessor (`quantity × unit_price`, both frozen at order time).

**`payments`/`disputes` — deliberately NOT built (Level 3 deviation, matches the brief's explicit boundary).** No real payment gateway is in scope for Step 10 (or any step so far) — `orders.payment_method`/`payment_status` are placeholder fields (`cash_on_delivery` always `pending`; `mock_card` always immediately `paid`, since there's no real gateway to fail). A `payments` table with no real transaction to record would be schema without a purpose. Similarly, no refund/returns workflow exists, so `disputes` has nothing to reference. Both remain legitimate future work if a real payment integration is ever scoped.

### Recommendation Engine Data — ✅ Implemented (Step 9, 2026-07-03) — schema deliberately smaller than originally proposed

**`user_interactions`** — ✅ **Implemented.** The source-of-truth event log every algorithm derives from.
- `id`, `user_id` (FK, `nullOnDelete`, nullable), `product_id` (FK, `nullOnDelete`, nullable — null for non-product interactions like search), `interaction_type` (string, backed by the `InteractionType` PHP enum: `viewed` / `wishlisted` / `wishlist_removed` / `cart_added` / `cart_removed` / `purchased` / `rated` / `recommendation_clicked` / `search_query`), `weight` (float, per-type default from the enum, overridable via `config('recommendation.interaction_weights')`), `metadata` (JSON — e.g. `{"query": "..."}` for search, `{"rating": 4}` for ratings, `{"module": "home"}` for clicks), `created_at` only (no `updated_at` — interactions are immutable events). Indexed on `(user_id, interaction_type)` and `(product_id, interaction_type)`.
- Migration: `2026_07_03_075004_create_user_interactions_table.php`. Model: `App\Models\UserInteraction`, native enum cast on `interaction_type`.

**`recommendation_logs`** — ✅ **Implemented.** Explainability + click-through tracking + the raw material for offline evaluation.
- `id`, `user_id` (FK, `cascadeOnDelete`), `product_id` (FK, `cascadeOnDelete`), `module` (string — `home`, `product_detail`, `recommendations_page`), `algorithm_source` (string — `content` / `collaborative` / `popularity` / `hybrid`), `score` (float, final blended score), `confidence` (float, 0–1), `reason` (human-readable string), `shown_at`, `clicked_at` (nullable, set by the click-tracking redirect). Indexed on `(user_id, module)` and `product_id`.
- Migration: `2026_07_03_075006_create_recommendation_logs_table.php`. Model: `App\Models\RecommendationLog`, `public $timestamps = false` (manages `shown_at` itself; there is no `created_at` column).

**`user_preferences` and `trending_scores` — deliberately NOT built (Level 3 deviation from the original proposal, flagged in PROJECT_DECISIONS.md).** The original plan had a nightly scheduled job recompute these as aggregate tables, so request-time work stayed cheap. This environment has no queue worker or cron running, so that job would never actually run — the tables would either sit permanently stale or need synchronous recomputation anyway, at which point they add a staleness/sync-consistency risk without the intended performance win. Instead:
- **User preference profile:** computed live, per request, directly from `user_interactions` inside `ContentBasedService::buildProfile()` (weighted category/color affinity, price range from observed min/max) — no separate table, no recompute schedule.
- **Trending scores:** computed live, per request, inside `PopularityService::trendingCounts()` (interaction counts within a rolling window) — no separate table.
- **What IS cached instead:** the *derived recommendation output* (the final ranked, scored, explained list for a given user/product/context), via `RecommendationCacheService`'s version-counter cache keys — invalidated precisely on the actions that would change it (wishlist/cart/rating changes bump that user's version; a product update/delete bumps the global version). This achieves the "don't recompute on every request" goal the original tables existed for, without the staleness risk.
- If a real queue worker/scheduler is introduced in a later phase, reintroducing precomputed aggregate tables (closer to the original proposal) would be the natural next step at a larger catalogue/user-base scale.

Full algorithmic design for how these tables get populated and queried lives in [RECOMMENDATION_ENGINE.md](RECOMMENDATION_ENGINE.md), now updated to reflect the actual implementation rather than the original plan.

### Administration — ✅ Implemented (Step 11, 2026-07-03)

**`audit_logs`** — ✅ **Implemented.** The project's first use of a polymorphic relation.
- `id`, `admin_id` (FK → users, **nullable**, `nullOnDelete`), `action` (string, backed by the `AuditAction` PHP enum — 17 cases: user suspended/activated/role-changed, vendor approved/rejected/suspended/restored, product approved/rejected/hidden/archived/restored, category created/updated/archived/restored, settings updated), `auditable_type`/`auditable_id` (nullable polymorphic morph columns — the moderated `User`/`VendorProfile`/`Product`/`Category` row, or null for actions with no single subject like Settings), `old_values`/`new_values` (JSON, nullable), `reason` (text, nullable — required at the Form Request layer for vendor/product moderation, optional for category actions), `created_at` only (no `updated_at` — an audit entry is immutable). Indexed on `(admin_id, action)`.
- Migration: `2026_07_03_162044_create_audit_logs_table.php`. Model: `App\Models\AuditLog`, `belongsTo` `admin` (aliased to `User`), `morphTo` `auditable`.
- **Written to via explicit `AuditLogService::record()` calls** from inside each `Admin\*Service` method — deliberately not an Eloquent Observer, since an observer on `User`/`Product`/`Category`/`VendorProfile` would also fire for ordinary buyer/vendor self-service edits, which must never appear in the administrator trail. See PROJECT_DECISIONS.md for the full reasoning.
- **Polymorphic over one-nullable-FK-per-entity:** chosen because the list of auditable entities (currently 4, User/VendorProfile/Product/Category) would otherwise force a schema migration every time Step 11 (or a later step) adds a new moderated entity type.

**`settings`** — ✅ **Implemented.** A deliberately small key-value store, not a generic configuration framework.
- `id`, `key` (string, unique), `value` (text, nullable — JSON-encoded so numbers/strings/booleans all round-trip cleanly), timestamps.
- Migration: `2026_07_03_173355_create_settings_table.php`. Model: `App\Models\Setting`.
- Exactly 6 keys are ever written: `site_name`, `delivery_cost_standard`, `delivery_cost_express`, `recommendation_weight_content`, `recommendation_weight_collaborative`, `recommendation_weight_popularity` — plus a `maintenance_mode` boolean flag read directly by `CheckMaintenanceMode` middleware (not overlaid onto `config()`, since maintenance mode isn't a config value anything else reads).
- **Config-overlay pattern:** `SettingsService::applyOverlay()`, called from `AppServiceProvider::boot()` (guarded by `Schema::hasTable('settings')` for pre-migration artisan runs), copies each saved value onto its matching `config()` path (`app.name`, `shipping.delivery_costs.standard`/`.express`, `recommendation.weights.content`/`.collaborative`/`.popularity`) at every request boot. This is what lets `HybridRecommendationService` and `CheckoutService` keep reading `config()` exactly as before, with zero awareness that a `settings` table exists.
- **New `config/shipping.php`** (`delivery_costs.standard`/`.express`, defaults 200/500) — added specifically so Settings would have a config key to write to; `CheckoutService`'s previously-hardcoded `DELIVERY_COSTS` private constant (Step 10) was replaced with a `config('shipping.delivery_costs')` read, the one deliberate touch to a Step 10 file in all of Step 11.

---

## Relationship Summary

```
✅ IMPLEMENTED (Step 5):
users (1) ──── (1) vendor_profiles
users (1) ──── (∞) products [as vendor]
users (1) ──── (∞) wishlists, addresses, reviews

categories (1) ──── (∞) categories [self, parent/child]
categories (1) ──── (∞) products

products (1) ──── (∞) product_images
products (1) ──── (∞) collection_items ──── (1) collections
products (1) ──── (∞) wishlists, reviews

✅ IMPLEMENTED (Step 9):
users (1) ──── (∞) user_interactions
products (1) ──── (∞) user_interactions
users (1) ──── (∞) recommendation_logs
products (1) ──── (∞) recommendation_logs

✅ IMPLEMENTED (Step 10):
users (1) ──── (1) carts
carts (1) ──── (∞) cart_items ──── (1) products
users (1) ──── (∞) orders [as buyer]
orders (1) ──── (∞) order_items ──── (0..1) products
order_items (∞) ──── (1) users [as vendor, denormalized]

⏸ PROPOSED — no real payment gateway or returns workflow in scope:
orders (1) ──── (1) payments
order_items (1) ──── (0..1) disputes

✅ IMPLEMENTED (Step 11):
users (1) ──── (∞) audit_logs [as admin]
audit_logs (∞) ──── (0..1) users|vendor_profiles|products|categories [polymorphic auditable]
```

---

## Decision Forks (need Product Owner input during review)

These are the places where more than one reasonable schema exists. Recommendation marked, but this is exactly the kind of Level 3 call that shouldn't be made unilaterally.

1. **Role modeling** — single `users` table + `role` enum + a `vendor_profiles` side table (proposed above) **vs.** fully separate `buyers`/`vendors`/`admins` tables.
   *Recommendation:* single table + role enum. Simpler auth (one `users` table, one login form), and buyers/vendors/admins share enough fields (name, email, password) that duplicating them is more overhead than benefit. Vendor-only fields live in the side table instead of nullable columns on `users`.
   **✅ Resolved 2026-07-02 (Step 1A):** single `users` table with `role` enum, implemented. `phone`/`address`/`profile_photo`/`status` also landed directly on `users` rather than being split out — reasonable for fields every role can plausibly have (a buyer has a delivery address too). Whether vendor-only fields (store name, approval status) still get a separate `vendor_profiles` table remains open — see note above.

2. **Product variants (size/color combinations)** — flat `products` table with a single `primary_color`/no size **vs.** full `product_variants` table (each variant has its own stock/SKU).
   *Recommendation:* flat for now (see Future Improvements) — the screenshots and brief don't show variant selection UI yet, and adding `product_variants` later is a additive, non-breaking migration. Don't build complexity the UI doesn't ask for yet.
   **✅ Resolved 2026-07-02 (Step 5):** implemented flat, per the recommendation.

3. **Multi-vendor orders** — one `orders` row can contain items from multiple vendors, split via `order_items.vendor_id` (proposed above) **vs.** forcing one order per vendor (cart auto-splits at checkout).
   *Recommendation:* single order with vendor-tagged items — standard marketplace pattern (matches Jumia/Amazon-marketplace conventions), simpler buyer-facing checkout UX, vendor-facing "my orders" view just filters by `vendor_id`.
   **✅ Resolved 2026-07-03 (Step 10):** implemented exactly per the recommendation — one `orders` row per checkout regardless of how many vendors' products are in the cart; `order_items.vendor_id` is what each `Vendor\OrderController` filters by, and `OrderPolicy` uses it to scope a vendor's visibility to only their own items within a shared order. The Step 10 brief's own boundary ("no multi-vendor checkout splitting") confirms this was the correct call, not just a reasonable one.

4. **Recommendation data granularity** — log every interaction as its own row in `user_interactions` (proposed above) **vs.** only maintaining live-updated aggregate counters on `products`/`users` (no event log).
   *Recommendation:* event log. It's the only option that supports "Because You Viewed," explainability (`recommendation_logs.reason`), and re-deriving preferences with different weights later without having thrown the raw data away — directly serves the brief's "explainable" and "academic, easy to defend" requirements.
   **✅ Resolved 2026-07-03 (Step 9):** implemented as an event log (`user_interactions`), per the recommendation. **Additionally decided (not part of the original fork):** the *aggregation* step (preference profiles, trending scores) is computed live per-request rather than precomputed into `user_preferences`/`trending_scores` tables — see the Recommendation Engine Data section above for the full reasoning.

5. **`users.address` vs. `addresses` table overlap** — **new fork surfaced in Step 5** (see the `vendor_profiles`/Identity section above). Not resolved; needs a decision on whether to drop `users.address`, keep it as a synced cache, or leave both.

---

## What This Document Does NOT Cover Yet

- Buyer vs. vendor registration form fields — **resolved (Step 5):** `name`/`email`/`phone`/`password` on `users`, `store_name` on `vendor_profiles` for vendors.
- Admin-specific tables (categories moderation queue, reports, audit log) — **implemented Step 11** (`audit_logs`, `settings`, `categories.deleted_at`); reports and recommendation analytics are read-only queries over existing tables, not new schema. See the Administration section above.
- Commerce tables — **implemented Step 10** (`carts`, `cart_items`, `orders`, `order_items`); `payments`/`disputes` deliberately never built, see the Commerce section above.
- Recommendation engine tables — **implemented Step 9** (`user_interactions`, `recommendation_logs`); `user_preferences`/`trending_scores` deliberately never built, see the Recommendation Engine Data section above.
- Vendor approval enforcement — **implemented Step 11.** Administrators can now approve/reject/suspend/restore a vendor via the admin Vendor Management area, all requiring a reason and logged to `audit_logs`. `vendor_profiles.approval_status` is still not a *gate* on the vendor's own dashboard/product/store access (a `pending` vendor still has full self-service access, same as Step 7) — Step 11 added administrator-facing moderation *actions*, not a buyer/vendor-facing access gate keyed off `approval_status`.

---

## Future Improvements

- `product_variants` table (size/color/SKU-level stock) — deferred per Decision Fork #2 above until the UI actually needs variant selection.
- Soft deletes (`deleted_at`) on user-facing content tables (`products`, `reviews`, `collections`) instead of hard deletes — worth deciding before Phase 4, not urgent now.
- Full-text search indexing strategy for `products.name`/`description` (MySQL FULLTEXT vs. a dedicated search service) — only matters once Phase 4 search is in scope.
- Guest browsing interaction tracking (pre-login, session-based) so first-session recommendations aren't cold — noted in `user_interactions.user_id` as "nullable, TBD" above; needs its own decision pass.
- Internationalization of category/product names if multi-language ever gets prioritized (see UI_BLUEPRINT.md Future Improvements).
- **`users.address` vs. `addresses` table overlap** (Step 5) — needs a decision, see Decision Fork #5.
- No factories exist for the new models — **Step 8 still didn't add them**, seed/demo data was created ad hoc via `tinker` instead. Worth adding proper `Product`/`Collection` factories once there's a recurring need to regenerate demo data (e.g. before a viva demo).
- `App\Models\Collection` shares a name with `Illuminate\Support\Collection` (Laravel's own collection class) — already-established naming from this document's original draft, not new in Step 5, but worth remembering to fully-qualify or alias imports in files that need both.
- No automated tests exist for the new migrations/models/seeder — verified manually via `php artisan tinker` and a live vendor registration round-trip this step.
- `collections`/`collection_items` remain completely unpopulated — no admin or vendor UI exists to curate a collection. `is_featured` (Step 8) was added specifically because of this gap; once curation UI exists, "Featured" could be revisited as a proper curated collection instead of a flat boolean.
- `reviews` table still has zero rows — `withAvg`/`withCount` on it correctly return null/0 everywhere, but the rating UI has never been exercised with real data. `InteractionType::Rated` and `InteractionTrackingService::recordRating()` exist and are ready to consume real ratings once a review-writing feature is built, but nothing calls them yet.
- **`user_preferences`/`trending_scores` — resolved not-to-build (Step 9),** not merely deferred. If a queue worker/scheduler is ever introduced and the catalogue/user-base grows large enough that live computation becomes expensive, reintroducing precomputed aggregate tables closer to the original proposal would be the natural next step — see PROJECT_DECISIONS.md.
- No factories exist for `UserInteraction`/`RecommendationLog` either — same gap as the Step 8 note above, verification data was created via `InteractionTrackingService` calls in `tinker` and real browser interaction instead.
- **Demo/seed data gap resolved (Step 10 health check):** the Step 8/9 note above ("no factories, seed data created ad hoc") turned out to be a real risk, not just a documentation gap — running `migrate:fresh --seed` during Step 10's health check wiped the ad-hoc demo vendor/products/buyer because they were never captured in a seeder. Fixed with `database/seeders/DemoCatalogueSeeder.php`. `Cart`/`Order`/`OrderItem` still have no factories either — same testing debt, worth batching together.
- `payments`/`disputes` remain unbuilt by design (Step 10) — revisit only if a real payment gateway or returns workflow is ever scoped; don't add speculative schema for either ahead of that.
- **Soft deletes now exist (Step 11), but on `categories` only** — the broader "soft deletes on user-facing content tables" idea noted above (originally flagged before Phase 4) was deliberately *not* extended to `products`/`reviews`/`collections`; Step 11 scoped it narrowly to the one table with an actual `cascadeOnDelete()` risk. Worth revisiting per-table if a future step needs undo-delete on products/reviews too.
- `products.status`'s 3 values (`draft`/`published`/`archived`) now carry two distinct admin meanings for the "archived" state ("temporarily hidden" vs. "deliberately archived") that only exist in the `audit_logs` trail, not as a queryable column — see PROJECT_EVOLUTION.md's Future Improvements for the "Hide vs Archive" note.
- `vendor_profiles.approval_status` still has no "suspended" value — Step 11 modeled vendor suspend/restore via the vendor's own `users.status` instead. A future pass could add `suspended` to `approval_status` directly if a report ever needs to distinguish "rejected" from "suspended" vendors at that column specifically.
- No factories exist for `AuditLog`/`Setting` — same testing debt noted for earlier steps' models; verification data for Step 11 was created via real browser interactions and direct `tinker` queries.
