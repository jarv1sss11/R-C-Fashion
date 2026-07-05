# RECOMMENDATION_ENGINE.md

**✅ Implemented (Step 9, 2026-07-03).** Recommendation architecture is a **Level 3 decision** (see [MASTER_BLUEPRINT.md](MASTER_BLUEPRINT.md) §8) — this document was reviewed as a plan, then built directly per a fully-specified Step 9 brief. What follows describes what was actually built; sections that changed from the original plan are marked, and the original plan's exact working is kept struck-through-in-spirit (annotated, not deleted) so the reasoning trail stays visible.

Status: **Implemented.** `app/Services/Recommendation/*`, `app/Repositories/RecommendationRepository.php`, `app/DTOs/*`, `config/recommendation.php`, `php artisan recommendations:evaluate`. See PROJECT_DECISIONS.md and PROJECT_EVOLUTION.md for the full build record, bugs found/fixed, and verification detail.
Last updated: 2026-07-03

---

## Philosophy (implemented as designed)

- No AI APIs, no ChatGPT/external recommendation services — confirmed: nothing in `app/Services/Recommendation/*` makes a network call.
- **Hybrid** — `HybridRecommendationService` combines Content-Based, Collaborative Filtering, and Popularity rather than relying on one.
- **Deterministic** — same `user_interactions` state always produces the same ranked output; no randomness, no trained model.
- **Explainable** — every `RecommendationResult` carries a human-readable `reason` string (`RecommendationScore::reason`), rendered directly on every recommendation card — never an anonymous "Recommended For You" tile.
- **Fast** — request-time work is real computation (not a lookup against a precomputed table, see the Data Inputs section below for why), but cached at the output level via `RecommendationCacheService` so repeat requests are cheap.
- **Offline** — no dependency on any external network call.
- **Academic** — `php artisan recommendations:evaluate` runs leave-one-out validation and prints Precision@K/Recall@K/MAP@K/NDCG@K/Coverage/Diversity/Novelty per algorithm, for direct viva defensibility.

This is *not* a machine-learning recommendation system. No matrix factorization, no embeddings, no trained models. Every mechanism below is a hand-written, inspectable rule or formula — verified by reading the actual service source, not just this document.

---

## Data Inputs — ✅ Implemented

Everything is derived from the `user_interactions` event log (see [DATABASE_BLUEPRINT.md](DATABASE_BLUEPRINT.md)). Actual weights, `App\Enums\InteractionType::defaultWeight()`, overridable per-type via `config('recommendation.interaction_weights')`:

| Interaction | Weight | Notes |
|---|---|---|
| Viewed | 1.0 | |
| Wishlisted | 3.0 | |
| Wishlist Removed | -2.0 | negative signal — `isPositiveSignal()` returns false |
| Cart Added | 4.0 | |
| Cart Removed | -2.0 | negative signal |
| Purchased | 5.0 | tracked, not yet writeable anywhere (no checkout — Step 11) |
| Rated | 3.5 | tracked, not yet writeable anywhere (no review UI) |
| Recommendation Clicked | 2.0 | written by the click-tracking redirect |
| Search Query | 1.0 | `product_id` is null for this type |

**Deviation from the original plan:** no recency-decay function was implemented. `ContentBasedService`/`CollaborativeFilteringService` weight interactions by type only, not by age. This was a deliberate scope cut, not an oversight — recency decay is a Level 2 tuning refinement, and the brief's Step 9 scope didn't ask for it; the `positiveInteractionsForUser($userId, ?int $days)` repository method already accepts an optional day-window if a future pass wants to add windowed decay without new schema. Flagged in Future Improvements below.

---

## "User Preference Profile" — ✅ Implemented differently than originally planned

**No `user_preferences` table exists** (see DATABASE_BLUEPRINT.md's Recommendation Engine Data section for the full reasoning) — the preference profile is computed **live, per request**, not as a periodically-recomputed stored row:

1. `ContentBasedService::buildProfile()` pulls the user's *positive* `user_interactions` (no day window applied — see above).
2. Each interaction's `weight` accumulates into per-category and per-color totals, then normalizes each map against its own max (0–1 range) — `normalize()`.
3. Price range is derived as `[min(observed prices) × 0.7, max(observed prices) × 1.3]` — a permissive band around what the user has actually engaged with, not a tight percentile.
4. Nothing is stored — the profile exists only for the duration of the request that computed it.

Recomputation trigger: **every cache-miss**, not a nightly schedule — there is no scheduler/queue worker running in this environment, so "nightly" was never actually achievable; see PROJECT_DECISIONS.md for the full trade-off discussion. The *output* (the ranked, scored list), not the profile, is what gets cached.

---

## Hybrid Mechanisms — ✅ Implemented (3 of the originally-sketched 4)

### 1. Content-based filtering — `app/Services/Recommendation/ContentBasedService.php`
Scores every catalogue candidate against the live-built profile above: `score = 0.5×category_affinity + 0.3×color_affinity + 0.2×price_affinity`. Also powers item-item "Similar Products" (`similarProducts()`) via a simpler same-category + same-color + price-proximity score, independent of any user. Results are re-ranked via a round-robin diversity pass (`diversify()`) so one category can't dominate the top-N — a refinement beyond the original plan's plain scored list.

### 2. Collaborative Filtering ("People With Similar Taste") — `app/Services/Recommendation/CollaborativeFilteringService.php`
Exactly as planned: Jaccard similarity (intersection over union) between the target user's positively-interacted product set and every other user's, filtered by `config('recommendation.collaborative.similarity_threshold')`, capped at `max_similar_users`. No training, no vectors.

### 3. Popularity / Trending — `app/Services/Recommendation/PopularityService.php`
Blended signal (trending window count, all-time most-viewed, all-time most-wishlisted, average rating, new-arrival flag, featured bonus), each config-weighted (`config('recommendation.popularity_weights')`). This is the universal fallback — always computable, confidence always `1.0` — matching the plan's "Trending in Kenya" role but generalized to cover new-arrival and featured signals too rather than being a separate module.

### 4. Rule-based modules — ⏸ NOT implemented (explicit Step 9 scope cut)
"Complete Your Outfit" (category-complementary rules), "Recommended Vendors", "Recommended Collections", "Recently Viewed" (as a literal module), and per-facet modules (Wishlist/Search/Category/Purchase/Color/Brand/Style-based suggestions as *separate* UI modules) were **not built**. The Step 9 brief scoped this to three algorithms + a hybrid blend + explainability + evaluation — not the full module catalogue originally sketched in Phase 1 planning. These remain legitimate future work, not abandoned ideas — see Future Improvements below.

### Module → Mechanism Map (updated to reflect what's actually built)

| Module (originally planned) | Status | Actual mechanism |
|---|---|---|
| Personalized For You | ✅ Built, as **"Recommended For You"** (Home) | `HybridRecommendationService` (Content + Collaborative + Popularity blend) |
| Because You Viewed / Similar Products | ✅ Built, as **"Similar Products"** (Product Detail) | `ContentBasedService::similarProducts()` |
| Trending in Kenya | ✅ Built, folded into the Popularity signal and exposed via `RecommendationService::trending()` | `PopularityService` |
| People With Similar Taste | ✅ Built, folded into the Hybrid blend (not a standalone UI module) | `CollaborativeFilteringService` |
| Continue Exploring | ⏸ Not built | — |
| Complete Your Outfit | ⏸ Not built | — |
| Recommended Vendors | ⏸ Not built | — |
| Recommended Collections | ⏸ Not built | — |
| Recently Viewed | ⏸ Not built (as a UI module — the underlying `Viewed` interactions are logged and available) | — |
| New Arrivals You May Like | ✅ Folded into Popularity's `new_arrival` signal, not a standalone module | `PopularityService` |
| Wishlist/Search/Category/Purchase/Color/Brand/Style-based suggestions | ⏸ Not built as separate modules — all feed into the single Content-Based profile instead | `ContentBasedService` |

---

## Scoring — ✅ Implemented

Actual blended form, `HybridRecommendationService::recommendForUser()`:

```
final_score = (w_content × content_score) + (w_collaborative × collaborative_score) + (w_popularity × popularity_score)
```

Where `w_content`/`w_collaborative`/`w_popularity` start from `config('recommendation.weights')` (content 0.50 / collaborative 0.35 / popularity 0.15 by default — **never hardcoded in the service itself**) but are **proportionally redistributed** among whichever algorithms actually returned results for this user before blending — e.g. a user with no collaborative signal has content/popularity's weights renormalized to sum to 1.0 between just those two, rather than leaving 35% of the score potential on the floor. This redistribution mechanism replaces the original plan's flat `w1`–`w4` formula with something that adapts per-request without any `if ($isNewUser)`-style branching.

## Explainability — ✅ Implemented exactly as planned

Every `RecommendationResult::reason()` returns a human-readable string generated by whichever algorithm scored it (e.g. "Because you like Men", "Popular among shoppers with similar taste (42% match)", "Trending this week", "Similar to Men Cotton Tee"). Persisted to `recommendation_logs.reason` on every `logShown()` call, and rendered directly under every recommendation card in the UI — confirmed live, no recommendation is ever shown without one.

## Performance Notes — updated for the actual (no-scheduler) implementation

- There is no scheduled/background job — see the User Preference Profile section above for why the original "nightly recompute" plan couldn't apply in this environment.
- Request-time work is real computation over `user_interactions` + the product catalogue, not a lookup against a precomputed vector — but the **output** is cached (`RecommendationCacheService`, 30-minute TTL, version-counter invalidation) so repeat requests for the same user/context are cheap regardless.
- All of this is offline/local — no external service calls, consistent with the "no AI APIs" constraint.
- Every generation event logs its execution time (milliseconds) and cache hit/miss to the `recommendations` log channel — this is the actual mechanism for observing whether the "fast" goal is being met, rather than an assumption.

---

## What This Document Does NOT Cover

- Recency decay for interaction weights — deliberately not implemented, see Data Inputs above.
- UI presentation details beyond what's built (carousel behavior, item counts) — see UI_BLUEPRINT.md's Recommendations page section for the actual implemented layout.
- Cold-start handling — **now implemented**, see Future Improvements below for what "cold start" actually resolves to in this system.

---

## Future Improvements

- **Cold-start — resolved, not just handled:** a brand-new user with zero interactions gets a pure Popularity result set (Content/Collaborative both return empty arrays, their weight fully redistributes to Popularity) — verified live during Step 9 browser testing. No special-cased "if new user" code path exists; this falls out of the confidence-weighted redistribution mechanism itself.
- Guest (pre-login) interaction tracking — still not implemented. `user_interactions.user_id` is nullable at the schema level (supports it), but every route that calls `InteractionTrackingService` sits behind the `auth` middleware today, so `$user` is never actually null in practice. Worth revisiting if guest browsing is ever unlocked.
- Recency decay (see Data Inputs) — the repository already supports a day-windowed query (`positiveInteractionsForUser($userId, $days)`); wiring an actual decay curve on top is a contained follow-up.
- "Complete Your Outfit", "Recommended Vendors", "Recommended Collections", "Recently Viewed", and per-facet standalone modules — legitimate future modules, deliberately out of Step 9's scope (see Hybrid Mechanisms §4 above), not abandoned.
- A/B testing harness for comparing weight configurations — `recommendations:evaluate` gives point-in-time comparison across algorithms, but not a true A/B split; not needed for the current academic-defense use case.
- Seasonal/time-of-day weighting for trending — Kenya-market-specific enhancement, not in the current `PopularityService` signal set.
- No feature/route tests exist for the recommendation engine — same testing debt as every prior step; verified manually via `tinker`, the `recommendations:evaluate` command, and in-browser interaction, not PHPUnit.
- If a queue worker/scheduler is ever introduced, precomputed `user_preferences`/`trending_scores` tables become worth reconsidering at meaningfully larger scale — see DATABASE_BLUEPRINT.md.
