<?php

namespace App\Repositories;

use App\DTOs\RecommendationScore;
use App\Enums\InteractionType;
use App\Models\Product;
use App\Models\RecommendationLog;
use App\Models\UserInteraction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Sole point of data access for interaction/recommendation-log persistence.
 * Algorithm services never query these tables directly — they go through
 * this repository, so the storage shape can change without touching
 * ContentBasedService/CollaborativeFilteringService/etc.
 */
class RecommendationRepository
{
    public function logInteraction(
        ?int $userId,
        ?int $productId,
        InteractionType $type,
        array $metadata = [],
    ): UserInteraction {
        $weight = config("recommendation.interaction_weights.{$type->value}", $type->defaultWeight());

        return UserInteraction::create([
            'user_id' => $userId,
            'product_id' => $productId,
            'interaction_type' => $type,
            'weight' => $weight,
            'metadata' => $metadata,
        ]);
    }

    public function interactionsForUser(int $userId, ?int $days = null): Collection
    {
        return $this->scopeToWindow(
            UserInteraction::query()->with('product')->where('user_id', $userId),
            $days
        )->get();
    }

    public function positiveInteractionsForUser(int $userId, ?int $days = null): Collection
    {
        return $this->interactionsForUser($userId, $days)
            ->filter(fn (UserInteraction $interaction) => $interaction->interaction_type->isPositiveSignal())
            ->values();
    }

    /**
     * Product IDs the user has already wishlisted, carted, or purchased —
     * used to avoid recommending what someone already has.
     */
    public function excludedProductIds(int $userId): array
    {
        return UserInteraction::query()
            ->where('user_id', $userId)
            ->whereIn('interaction_type', [
                InteractionType::Wishlisted->value,
                InteractionType::CartAdded->value,
                InteractionType::Purchased->value,
            ])
            ->whereNotNull('product_id')
            ->distinct()
            ->pluck('product_id')
            ->all();
    }

    /**
     * user_id => Collection<product_id> for every other user with at least
     * one positive interaction — the raw material for similarity comparison.
     */
    public function positiveProductSetsByUser(int $excludeUserId, ?int $days = null): Collection
    {
        return $this->scopeToWindow(
            UserInteraction::query()
                ->whereNotNull('product_id')
                ->whereNotNull('user_id')
                ->where('user_id', '!=', $excludeUserId),
            $days
        )
            ->get()
            ->filter(fn (UserInteraction $interaction) => $interaction->interaction_type->isPositiveSignal())
            ->groupBy('user_id')
            ->map(fn (Collection $interactions) => $interactions->pluck('product_id')->unique()->values());
    }

    public function interactionCountsByProduct(InteractionType $type, ?int $days = null): Collection
    {
        return $this->scopeToWindow(
            UserInteraction::query()->where('interaction_type', $type->value),
            $days
        )
            ->selectRaw('product_id, COUNT(*) as total')
            ->groupBy('product_id')
            ->pluck('total', 'product_id');
    }

    public function logRecommendationShown(int $userId, Product $product, string $module, RecommendationScore $score): RecommendationLog
    {
        return RecommendationLog::create([
            'user_id' => $userId,
            'product_id' => $product->id,
            'module' => $module,
            'algorithm_source' => $score->algorithmSource,
            'score' => $score->finalScore,
            'confidence' => $score->confidence,
            'reason' => $score->reason,
            'shown_at' => now(),
        ]);
    }

    public function markRecommendationClicked(int $userId, int $productId, string $module): void
    {
        RecommendationLog::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('module', $module)
            ->whereNull('clicked_at')
            ->latest('shown_at')
            ->limit(1)
            ->update(['clicked_at' => now()]);
    }

    public function recommendationLogsForUser(int $userId, ?string $module = null): Collection
    {
        return RecommendationLog::query()
            ->where('user_id', $userId)
            ->when($module, fn ($query) => $query->where('module', $module))
            ->get();
    }

    /**
     * Most recent distinct products this user has viewed — the raw material
     * for the "Recently Viewed" widget. A plain read of existing `Viewed`
     * interactions, not a scored recommendation.
     */
    public function recentlyViewedProductIds(int $userId, int $limit = 8, ?int $excludeProductId = null): array
    {
        return UserInteraction::query()
            ->where('user_id', $userId)
            ->where('interaction_type', InteractionType::Viewed->value)
            ->whereNotNull('product_id')
            ->when($excludeProductId, fn ($query, $value) => $query->where('product_id', '!=', $value))
            ->latest('created_at')
            ->limit(100)
            ->pluck('product_id')
            ->unique()
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * "Customers who viewed this also viewed" — a co-occurrence read over
     * the same `user_interactions` table, not a new signal or algorithm.
     */
    public function coViewedProductIds(int $productId, int $limit = 8): array
    {
        $viewerIds = UserInteraction::query()
            ->where('product_id', $productId)
            ->where('interaction_type', InteractionType::Viewed->value)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        if ($viewerIds->isEmpty()) {
            return [];
        }

        return UserInteraction::query()
            ->whereIn('user_id', $viewerIds)
            ->where('product_id', '!=', $productId)
            ->where('interaction_type', InteractionType::Viewed->value)
            ->selectRaw('product_id, COUNT(*) as total')
            ->groupBy('product_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->pluck('product_id')
            ->all();
    }

    private function scopeToWindow($query, ?int $days)
    {
        if ($days !== null) {
            $query->where('created_at', '>=', Carbon::now()->subDays($days));
        }

        return $query;
    }
}
