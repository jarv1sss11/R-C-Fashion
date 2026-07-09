<?php

namespace App\Enums;

/**
 * Every behavioural signal the recommendation engine can learn from.
 * Default weights live in config/recommendation.php ('interaction_weights')
 * rather than hardcoded here, so tuning them never requires a code change.
 */
enum InteractionType: string
{
    case Viewed = 'viewed';
    case Wishlisted = 'wishlisted';
    case WishlistRemoved = 'wishlist_removed';
    case CartAdded = 'cart_added';
    case CartRemoved = 'cart_removed';
    case Purchased = 'purchased';
    case Rated = 'rated';
    case RecommendationClicked = 'recommendation_clicked';
    case SearchQuery = 'search_query';

    public function defaultWeight(): float
    {
        return match ($this) {
            self::Viewed => 1.0,
            self::SearchQuery => 1.5,
            self::RecommendationClicked => 2.0,
            self::Wishlisted => 3.0,
            self::CartAdded => 4.0,
            self::Rated => 4.0,
            self::Purchased => 5.0,
            // Negative signals: counteract the positive action they undo,
            // rather than contributing no information at all.
            self::WishlistRemoved => -2.0,
            self::CartRemoved => -2.0,
        };
    }

    /**
     * Whether this interaction type represents a positive (product-affirming)
     * signal — used by the evaluator to build "relevant" ground-truth sets
     * and by Content-Based/Collaborative filtering to build preference profiles.
     *
     * SearchQuery returns false: a search is user intent ("I am looking for X"),
     * not engagement with a specific product.  Its product_id is also null, so
     * it is already excluded from every product-set operation, but returning
     * false here ensures the type system expresses that truth explicitly rather
     * than relying on a nullable column as a side-channel guard.
     */
    public function isPositiveSignal(): bool
    {
        return ! in_array($this, [self::WishlistRemoved, self::CartRemoved, self::SearchQuery], true);
    }
}
