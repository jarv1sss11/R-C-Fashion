<?php

namespace App\Services\Recommendation;

use App\Enums\InteractionType;
use App\Models\Product;
use App\Models\User;
use App\Repositories\RecommendationRepository;

/**
 * The only place the rest of the app should touch to record behavioural
 * data. Controllers call these methods; nothing outside this class needs
 * to know about InteractionType weights or the underlying table shape.
 * New interaction types can be added here without changing any existing
 * caller.
 */
class InteractionTrackingService
{
    public function __construct(private readonly RecommendationRepository $repository)
    {
    }

    public function recordView(?User $user, Product $product): void
    {
        $this->repository->logInteraction($user?->id, $product->id, InteractionType::Viewed);
    }

    public function recordWishlist(?User $user, Product $product): void
    {
        $this->repository->logInteraction($user?->id, $product->id, InteractionType::Wishlisted);
    }

    public function recordWishlistRemoval(?User $user, Product $product): void
    {
        $this->repository->logInteraction($user?->id, $product->id, InteractionType::WishlistRemoved);
    }

    public function recordCartAddition(?User $user, Product $product): void
    {
        $this->repository->logInteraction($user?->id, $product->id, InteractionType::CartAdded);
    }

    public function recordCartRemoval(?User $user, Product $product): void
    {
        $this->repository->logInteraction($user?->id, $product->id, InteractionType::CartRemoved);
    }

    public function recordPurchase(?User $user, Product $product): void
    {
        $this->repository->logInteraction($user?->id, $product->id, InteractionType::Purchased);
    }

    public function recordRating(?User $user, Product $product, int $rating): void
    {
        $this->repository->logInteraction($user?->id, $product->id, InteractionType::Rated, ['rating' => $rating]);
    }

    public function recordRecommendationClick(?User $user, Product $product, string $module): void
    {
        $this->repository->logInteraction($user?->id, $product->id, InteractionType::RecommendationClicked, ['module' => $module]);
    }

    public function recordSearch(?User $user, string $query): void
    {
        $this->repository->logInteraction($user?->id, null, InteractionType::SearchQuery, ['query' => $query]);
    }
}
