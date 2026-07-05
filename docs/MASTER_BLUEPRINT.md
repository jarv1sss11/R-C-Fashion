# MASTER_BLUEPRINT.md

**R&C Fashion Recommendation Platform — Version 2**
Single source of truth for vision, architecture, navigation, user journeys, database direction, recommendation design, milestones, rules, and workflow.

Status: **Guest experience, authentication, database core, Buyer/Vendor Modules, Product Catalogue, Recommendation Engine, Shopping Workflow & Order Management, and Administration & Management implemented (Steps 1–11).** See §9 for the full milestone breakdown.
Last updated: 2026-07-03

---

## 1. Vision

This is **not** a traditional e-commerce site. It is an **intelligent fashion discovery platform** for the Kenyan market.

- The recommendation engine is the product. The marketplace exists to support it.
- Shopping is the *result* of a successful recommendation, not the starting point.
- Success = users discover clothing they genuinely like, not just browse a catalog.

Design language target: Apple simplicity + Zara luxury + Pinterest discovery + editorial fashion + premium Kenyan marketplace. Elegant, spacious, minimalist, premium on every page.

The three supplied screenshots (Home / Login / Registration) are the **design contract**, not inspiration. They are reproduced pixel-for-intent (spacing, type, color, proportions, layout, navigation) rather than reinterpreted.

## 2. Technology Stack

| Layer | Choice |
|---|---|
| Backend | PHP 8.2 (XAMPP-bundled), Laravel 12 |
| Frontend | Blade templates, HTML5, CSS3, JavaScript (Vite-bundled assets) |
| Database | MySQL (`rc_fashion_recommendation`) |
| Local server | XAMPP |
| Version control | None yet — Git/GitHub deliberately deferred until a stable milestone |

Laravel 12.62.0 is installed. No previous project, database, migration, model, or Blade file has been copied or reused — everything is greenfield.

## 3. High-Level Architecture

Standard Laravel MVC, organized around four user roles that will be layered in over the phases below:

- **Guest** — homepage, login, registration only (Phase 3)
- **Buyer** — discovery feed, browsing, recommendations, cart/checkout (Phases 4, 8)
- **Vendor** — store management, inventory, analytics (Phase 5)
- **Admin** — moderation, approvals, system settings (Phase 6)

No controllers, models, or routes exist yet beyond Laravel's default scaffold. Route/controller/model structure for the guest pages will be proposed as part of Phase 3 implementation, following Laravel conventions (Level 1 decision — Claude proceeds without asking, per the decision-level rules in §7).

## 4. Navigation Map (Guest Phase)

Top nav: `Home · Men · Women · Accessories · Recommendations · Cart · Profile`

Guest click behavior — **everything except Home routes to Login**:

| Guest clicks | Result |
|---|---|
| Explore Collection | → Login |
| Get Outfit Suggestions | → Login |
| Men | → Login |
| Women | → Login |
| Accessories | → Login |
| Recommendations | → Login |
| Cart | → Login |
| Profile | → Login |

After successful authentication, the user is redirected back to whatever page they originally intended to reach (standard Laravel `intended()` redirect flow) — **implemented 2026-07-02 (Step 4)**.

**Authenticated behavior (added Step 4):** the same nav items now resolve via a `gated_route()` helper — guests still go to Login, but authenticated users go to `/coming-soon` (a labeled placeholder) instead, since bouncing an already-logged-in user back to the login form would be broken UX. "Profile" is replaced by the user's name + a Logout button once authenticated. See NAVIGATION_FLOW.md for the full routing detail.

**Admin nav (added Step 11):** an "Admin" nav link appears only for `role === 'admin'` users (alongside the existing vendor-only "Dashboard" link), pointing to `/admin` — the Administration & Management dashboard. See NAVIGATION_FLOW.md for the full `/admin/*` route map.

## 5. User Journeys

**Guest journey:**
```
Homepage → (click any gated action) → Login
Login → "Create Account" → Registration
Registration → choose I Want To Shop / I Want To Sell → accordion form → submit → Login (or auto-login, TBD Level 2 decision)
Login success → redirect to originally intended page
```

**Post-login (future phases, not yet in scope):**
```
Login → Buyer Discovery Feed (replaces generic homepage) → personalized recommendations first
```

## 6. Database Direction

- Database: `rc_fashion_recommendation` — MySQL, created fresh, nothing imported from any prior project. MySQL access resolved 2026-07-02 (see [[mysql_env_setup]] / PROJECT_DECISIONS.md).
- **Core schema implemented (Step 5, 2026-07-02):** `users` (Step 1A), `vendor_profiles`, `categories`, `products`, `product_images`, `collections`, `collection_items`, `wishlists`, `addresses`, `reviews` — 10 tables total, covering identity/vendors and the product catalog. See DATABASE_BLUEPRINT.md for full column definitions and relationships.
- **Recommendation-engine schema implemented (Step 9, 2026-07-03):** `user_interactions` (event log — 9 interaction types) and `recommendation_logs` (shown/clicked tracking for explainability + evaluation). **`user_preferences` and `trending_scores` were deliberately not built** — both signals are computed live from `user_interactions` on each request instead, with the derived recommendation *output* cached (not the raw aggregates). See DATABASE_BLUEPRINT.md and PROJECT_DECISIONS.md for the full reasoning.
- **Commerce schema implemented (Step 10, 2026-07-03):** `carts`, `cart_items`, `orders`, `order_items` — persistent cart replacing Step 8's session-based one, plus full order/inventory tracking. **`payments`/`disputes` deliberately not built** — no real payment gateway or returns workflow is in scope; `orders.payment_method`/`payment_status` are placeholder fields. See DATABASE_BLUEPRINT.md and PROJECT_DECISIONS.md for the full reasoning.
- **Administration schema implemented (Step 11, 2026-07-03):** `audit_logs` (polymorphic `auditable`, admin/action/old-new-values/reason) and `settings` (a small key-value store overlaying `config()` at boot). `categories` gained `SoftDeletes` — the **only** model in the project with soft deletes, since a hard delete would `cascadeOnDelete()` every product in it. See DATABASE_BLUEPRINT.md and PROJECT_DECISIONS.md for the full reasoning.
- Vendor approval is now enforced via the admin Vendor Management area (Step 11) — administrators can approve/reject/suspend/restore a vendor, all requiring a reason and logged to `audit_logs`. Vendor *registration* and the approval *workflow itself* remain exactly as built in Step 5; Step 11 only adds the admin-facing moderation actions on top.

## 7. Recommendation Engine Philosophy — ✅ Implemented (Step 9, 2026-07-03)

Guiding constraints, honored in the actual implementation:

- No AI APIs, no ChatGPT/external recommendation services — every algorithm (Content-Based, Collaborative Filtering, Popularity) is a hand-written, inspectable rule or formula.
- Hybrid, deterministic, explainable, fast, offline, academically defensible — `HybridRecommendationService` blends the three algorithms via configurable weights (`config/recommendation.php`), with confidence-weighted redistribution when an algorithm has no signal for a user (no hardcoded "new user" branches). Every recommendation carries a human-readable `reason` string.
- Every interaction (view, wishlist add/remove, cart add, purchase, rating, recommendation click, search) is tracked via `InteractionTrackingService` into `user_interactions` — the source-of-truth event log all three algorithms derive from live.
- **Implemented modules:** "Recommended For You" (Home, hybrid), "Similar Products" (Product Detail, content-based item-item similarity), a dedicated `/recommendations` page with an algorithm switcher for academic comparison, and offline evaluation (`RecommendationEvaluator`, `php artisan recommendations:evaluate`) computing Precision@K/Recall@K/MAP@K/NDCG@K/Coverage/Diversity/Novelty via leave-one-out validation.
- **Deliberately not built (out of Step 9's explicit scope):** "Complete Your Outfit", "Recommended Collections", "Recommended Vendors", "Continue Exploring" as separate modules, and any Orders/Checkout/Payments/Admin/Analytics/Chatbot/LLM/Image-Generation integration — see RECOMMENDATION_ENGINE.md for the full list of exclusions.

## 8. Decision Levels

| Level | Who decides | Examples |
|---|---|---|
| 1 | Claude, independently | Helper methods, folder organization, CSS organization, reusable components, naming, optimization, Laravel best practices |
| 2 | Claude recommends, waits for approval | Animation timing, layout improvements, validation behavior, recommendation weights, page-flow improvements |
| 3 | Claude never decides alone | Database schema, authentication changes, recommendation architecture, new tables, removing features, proposal scope, Git workflow, deployment architecture |

When anything is ambiguous: **stop, explain, recommend, wait.** Never assume.

## 9. Milestones — Phase Checklist

**Note on numbering:** this project has been driven page-by-page via a separate "Step N" implementation roadmap (Step 1 Home, Step 2 Login, Step 3 Registration, Step 4 Authentication, Step 5 Database Design, Step 6 Buyer Module, Step 7 Vendor Module, Step 8 Product Catalogue, Step 9 Recommendation Engine, Step 10 Shopping Cart, Step 11 Checkout, Step 12 Admin Dashboard, Step 13 Analytics, Step 14 Testing, Step 15 Documentation) which doesn't map one-to-one to the Phase 0–10 list below. The table is kept for the broader architectural grouping; the Step numbers in PROJECT_EVOLUTION.md are the actual chronological record.

| Phase | Scope | Status |
|---|---|---|
| 0 | Project foundation (new Laravel install, env, DB config, dependencies, folder structure) | ✅ Done |
| 1 | Master blueprint documentation (this document set) | ✅ Done (10-doc set, continuously updated) |
| 2 | Design system (colors, typography, components, spacing, animations — no business logic) | ✅ Done (built alongside Steps 1–3) |
| 3 | Guest experience (Homepage, Login, Registration) | ✅ Done — Steps 1–3 |
| 3b | Authentication (real login/logout/registration, session handling, rate limiting) | ✅ Done — Step 4. Guest/auth middleware wired; `gated_route()` sends authenticated users to a `/coming-soon` placeholder instead of bouncing them back to Login. **Forgot Password / Password Reset added in Phase 11.5**, using Laravel's built-in Password broker — see NAVIGATION_FLOW.md. Email Verification remains intentionally out of scope (see PROJECT_DECISIONS.md). |
| 3c | Core database design (catalog + vendor schema) | ✅ Done — Step 5. `vendor_profiles`, `categories`, `products`, `product_images`, `collections`, `collection_items`, `wishlists`, `addresses`, `reviews`. Recommendation-engine (Step 9) and commerce (Step 10) tables added in their own later steps, as planned. |
| 3d | Buyer Module (account profile, address book, wishlist view) | ✅ Done — Step 6. `/account`, `/account/addresses`, `/wishlist`, all `auth`-protected. First `Policy` (`AddressPolicy`) established for ownership checks. |
| 4 | Buyer discovery experience | ✅ Done — Step 8 (Product Catalogue: `/products`, `/products/{slug}`, `/categories/{slug}`, `/search`, filters, wishlist + basic session cart, minimal vendor storefront) + Step 9 (Recommendation Engine: "Recommended For You" on Home, "Similar Products" on Product Detail, dedicated `/recommendations` page). |
| 5 | Vendor experience (dashboard, inventory, store management) | ✅ Done — Step 7. `/vendor` dashboard (real stat counts), `/vendor/products` CRUD + image upload, `/vendor/store` profile + logo upload. New `vendor` middleware + `ProductPolicy`/`StorePolicy`. Analytics (beyond basic dashboard counts) and vendor approval *enforcement* still not built — `approval_status` exists and defaults `pending` but nothing gates on it yet. |
| 6 | Admin experience (user management, vendor approval, categories, reports, moderation) | ✅ Done — Step 11. `/admin` dashboard (6 widgets + notification center + 6 charts), User Management (suspend/activate/assign-admin), Vendor Management (approve/reject/suspend/restore + stats), Product Moderation (approve/reject/hide/archive/restore), Category Management (full CRUD + the project's only soft-delete), Reports (8 types, CSV export), Recommendation Analytics (reuses the unmodified `RecommendationEvaluator`), Audit Logging, Settings (site name/delivery costs/recommendation weights/maintenance mode), and a Health Dashboard. New `admin` middleware requiring both `role === 'admin'` and `status === 'active'`. |
| 7 | Intelligent recommendation engine (preferences, scores, hybrid logic, trending, logs) | ✅ Done — Step 9. Content-Based/Collaborative/Popularity algorithms + `HybridRecommendationService` blend, `InteractionTrackingService`, offline evaluator + Artisan command, full UI integration (Home/Product Detail/`/recommendations`). Collections-based/outfit-suggestion modules explicitly out of scope — see RECOMMENDATION_ENGINE.md. |
| 8 | Commerce (wishlist ✅ Step 8; cart/checkout/orders/inventory ✅ Step 10) | ✅ Done — Step 10. Persistent database cart (replacing Step 8's session cart), placeholder-payment checkout, `orders`/`order_items` schema with historical snapshotting, automatic inventory deduction, buyer order history/tracking, vendor order/fulfillment management, purchase signal fed into the Step 9 recommendation engine. Real payment gateways, refunds, coupons, multi-vendor checkout splitting explicitly out of scope — see DATABASE_BLUEPRINT.md/PROJECT_DECISIONS.md. |
| 9 | Analytics & optimization (performance, reports, caching, security, auditing) | Partially covered by Step 11 (Reports, Recommendation Analytics, Audit Logging, a read-only Health Dashboard) — performance profiling/optimization and a dedicated security pass remain not started. |
| 10 | Testing, documentation & deployment (Git/GitHub setup happens here, not before) | Not started — explicitly not to begin until the Product Owner approves proceeding past Step 11. |

**Phase 11.5 — Feature Completeness Audit (2026-07-04):** a read-only audit of the entire project for placeholder/dead/unfinished functionality, followed by implementation of the two items approved for completion (Forgot Password, Password Reset) and a fix to the Vendor Dashboard's stale "Pending Orders — coming in a later phase" stat (now a real `COUNT()` wired through the existing `OrderRepository`/`OrderService`). No new modules, subsystems, or scope were introduced. See PROJECT_EVOLUTION.md for the full report.

## 10. Development Workflow

```
Research → Blueprint → Architecture → Design System → Authentication →
Discovery Platform → Recommendation Engine → Commerce → Testing →
Documentation → Deployment
```

Architecture-first. No jumping straight into building pages.

## 11. Rules (non-negotiable)

- Never use the previous project as a base; never copy its files.
- Never silently redesign the supplied UI screenshots.
- Never make major decisions without approval (see Decision Levels, §8).
- Always explain recommendations before acting on Level 2/3 items.
- Keep documentation synchronized with development — update these files as phases progress, not retroactively.
- Build the recommendation engine from first principles, no external AI services.
- Treat the three screenshots as UI/UX references (layout, spacing, type, color, proportions) — **not** branding references. Brand is "R&C Fashion" everywhere.
- Keep the Product Owner heavily involved; assume they are always available to answer questions.

## 12. Blueprint Document Set

Planning documents live in `docs/` and are treated as living documents — updated continuously whenever a major decision changes, not written once and forgotten:

| Document | Covers |
|---|---|
| MASTER_BLUEPRINT.md | This document — vision, architecture, navigation, journeys, decision levels, milestones |
| UI_BLUEPRINT.md | Page-by-page UI spec, component trees, interactions, animations |
| DESIGN_SYSTEM.md | Colors, typography, spacing, buttons, cards, animation tokens |
| DATABASE_BLUEPRINT.md | Proposed entities/relationships — planning only, no migrations until approved |
| RECOMMENDATION_ENGINE.md | Hybrid deterministic recommendation design — planning only, Phase 7 scope |
| NAVIGATION_FLOW.md | Sitemap, routing, redirect rules across all roles |
| DEVELOPMENT_RULES.md | Coding standards, naming conventions, folder structure, future Git workflow |
| PROJECT_DECISIONS.md | Append-only log of every architectural decision, with reasoning |
| PROJECT_EVOLUTION.md | Append-only timeline of features added/removed/modified |
| PROPOSAL_CHANGES.md | Diff tracker against the official academic proposal (template until that document is supplied) |

As of 2026-07-02, the Product Owner has requested a **collaborative review checkpoint**: all ten documents are reviewed one-by-one before any UI/backend implementation begins. No migrations, models, controllers, or business logic get written from these documents until that review is complete.

## 13. Future Improvements

Ideas surfaced during planning that are intentionally postponed, not forgotten:

- Formal Git branching/workflow strategy — deferred until a stable milestone per the brief; will get its own decision entry in PROJECT_DECISIONS.md when it's time, informed by DEVELOPMENT_RULES.md's draft.
- CI/CD pipeline — not relevant until Phase 10 (Testing, Documentation & Deployment).
- Multi-language support (English/Kiswahili) — flagged in UI_BLUEPRINT.md, no decision yet.
- Formal API layer (if a future mobile app is ever wanted) — nothing in the brief suggests this is needed, noted only so it isn't accidentally designed against later.
- No "revoke admin" action exists yet (Step 11 only implemented promotion) — see PROJECT_EVOLUTION.md.
- Application version on the Health Dashboard is a hardcoded `'Step 11'` string, pending a real versioning scheme once/if Git is introduced.
