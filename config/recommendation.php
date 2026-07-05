<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Algorithm Toggles
    |--------------------------------------------------------------------------
    |
    | Disabling an algorithm here removes it from the hybrid blend entirely
    | (its weight is redistributed among whatever remains enabled) without
    | touching any algorithm's code — useful for the Chapter 5 evaluation's
    | "Content-only / Collaborative-only / Popularity-only / Hybrid" comparison.
    |
    */
    'enabled' => [
        'content' => true,
        'collaborative' => true,
        'popularity' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Hybrid Blend Weights
    |--------------------------------------------------------------------------
    |
    | Base weights for combining algorithm scores. These are a starting
    | point, not a fixed split — HybridRecommendationService redistributes
    | weight proportionally away from any algorithm that returns nothing for
    | a given user (e.g. a brand-new user with no Collaborative signal),
    | so cold-start naturally falls back to Content + Popularity without any
    | special-cased "if new user" branch in the algorithm code.
    |
    */
    'weights' => [
        'content' => 0.50,
        'collaborative' => 0.35,
        'popularity' => 0.15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Interaction Weights
    |--------------------------------------------------------------------------
    |
    | Default weight applied when InteractionTrackingService records an
    | interaction. Overrides InteractionType::defaultWeight() when set —
    | lets weights be tuned per deployment without a code change. Keyed by
    | the enum's string value.
    |
    */
    'interaction_weights' => [
        'viewed' => 1.0,
        'search_query' => 1.5,
        'recommendation_clicked' => 2.0,
        'wishlisted' => 3.0,
        'cart_added' => 4.0,
        'rated' => 4.0,
        'purchased' => 5.0,
        'wishlist_removed' => -2.0,
        'cart_removed' => -2.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Content-Based Signal Weights
    |--------------------------------------------------------------------------
    |
    | How much each product attribute contributes to ContentBasedService's
    | user-profile affinity score (must sum to 1.0). Extended in Phase 13.1
    | to cover the expanded catalogue metadata (brand/style/season/age_group/
    | tags) alongside the original category/color/price signals — tunable
    | here without touching ContentBasedService's code.
    |
    */
    'content_weights' => [
        'category' => 0.25,
        'brand' => 0.15,
        'style' => 0.15,
        'color' => 0.10,
        'price' => 0.10,
        'tags' => 0.10,
        'age_group' => 0.10,
        'season' => 0.05,
    ],

    /*
    |--------------------------------------------------------------------------
    | Collaborative Filtering
    |--------------------------------------------------------------------------
    */
    'collaborative' => [
        // Minimum Jaccard similarity for another user to count as "similar".
        'similarity_threshold' => 0.05,
        // How many similar users to draw candidate products from.
        'max_similar_users' => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Popularity Signal Weights
    |--------------------------------------------------------------------------
    |
    | How much each popularity signal contributes to a product's popularity
    | score. Independent of the hybrid blend weights above.
    |
    */
    'popularity_weights' => [
        'trending' => 0.35,
        'most_viewed' => 0.2,
        'most_wishlisted' => 0.2,
        'highest_rated' => 0.15,
        'new_arrival' => 0.1,
    ],

    // Rolling window, in days, for the "trending" popularity signal.
    'trending_window_days' => 7,

    // Rolling window, in days, for a product to count as a "new arrival".
    'new_arrival_window_days' => 14,

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    */
    'cache_minutes' => 30,

    /*
    |--------------------------------------------------------------------------
    | Output
    |--------------------------------------------------------------------------
    */
    'recommendation_limit' => 12,
];
