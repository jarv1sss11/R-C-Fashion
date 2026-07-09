<?php

namespace App\Services\Recommendation;

use App\DTOs\RecommendationResult;
use App\DTOs\RecommendationScore;
use App\Enums\InteractionType;
use App\Models\Product;
use App\Repositories\RecommendationRepository;
use App\Services\ProductCatalogueService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The fallback layer, not a personalization algorithm — it doesn't know or
 * care who's asking. Activates whenever Content-Based/Collaborative have
 * weak or no signal (new users, sparse data) and backs every other module
 * so a result set is never empty.
 */
class PopularityService
{
    public function __construct(
        private readonly RecommendationRepository $repository,
        private readonly ProductCatalogueService $catalogue,
    ) {
    }

    /**
     * $filters is passed straight through to ProductCatalogueService::query()
     * (e.g. ['category_id' => ..., 'gender' => ..., 'age_group' => ...]) so a
     * product-page caller can scope "popular" to the anchor product's own
     * category/gender/age_group. Defaults to empty/global, unchanged for the
     * standalone "Recommended For You" page and every other existing caller
     * — none of them pass this argument today.
     *
     * @return RecommendationResult[]
     */
    public function recommend(int $limit = 12, array $excludeProductIds = [], array $filters = []): array
    {
        $products = $this->catalogue->query($filters)
            ->when($excludeProductIds, fn ($query) => $query->whereNotIn('products.id', $excludeProductIds))
            ->get();

        if ($products->isEmpty()) {
            return [];
        }

        $weights = config('recommendation.popularity_weights');
        $trendingWindow = (int) config('recommendation.trending_window_days', 7);
        $newArrivalWindow = (int) config('recommendation.new_arrival_window_days', 14);

        $trending = $this->trendingCounts($trendingWindow);
        $mostViewed = $this->repository->interactionCountsByProduct(InteractionType::Viewed);
        $mostWishlisted = $this->repository->interactionCountsByProduct(InteractionType::Wishlisted);

        $trendingMax = $trending->isEmpty() ? 0 : $trending->max();
        $viewedMax = $mostViewed->isEmpty() ? 0 : $mostViewed->max();
        $wishlistedMax = $mostWishlisted->isEmpty() ? 0 : $mostWishlisted->max();
        $newArrivalCutoff = Carbon::now()->subDays($newArrivalWindow);

        $results = $products->map(function (Product $product) use (
            $weights, $trending, $mostViewed, $mostWishlisted,
            $trendingMax, $viewedMax, $wishlistedMax, $newArrivalCutoff,
        ) {
            $trendingScore = $trendingMax > 0 ? ($trending->get($product->id, 0) / $trendingMax) : 0.0;
            $viewedScore = $viewedMax > 0 ? ($mostViewed->get($product->id, 0) / $viewedMax) : 0.0;
            $wishlistedScore = $wishlistedMax > 0 ? ($mostWishlisted->get($product->id, 0) / $wishlistedMax) : 0.0;
            $ratingScore = $product->reviews_avg_rating ? ((float) $product->reviews_avg_rating / 5) : 0.0;
            $newArrivalScore = $product->created_at?->greaterThanOrEqualTo($newArrivalCutoff) ? 1.0 : 0.0;
            $featuredBonus = $product->is_featured ? 0.1 : 0.0;

            $score = ($weights['trending'] * $trendingScore)
                + ($weights['most_viewed'] * $viewedScore)
                + ($weights['most_wishlisted'] * $wishlistedScore)
                + ($weights['highest_rated'] * $ratingScore)
                + ($weights['new_arrival'] * $newArrivalScore)
                + $featuredBonus;

            $reason = $this->reasonFor($product, $trendingScore, $newArrivalScore, $ratingScore, $wishlistedScore);

            return new RecommendationResult(
                product: $product,
                score: new RecommendationScore(
                    contentScore: 0.0,
                    collaborativeScore: 0.0,
                    popularityScore: min(1.0, $score),
                    finalScore: min(1.0, $score),
                    confidence: 1.0, // popularity is always computable — it never "runs out" of signal
                    reason: $reason,
                    algorithmSource: 'popularity',
                    generatedAt: new \DateTimeImmutable(),
                ),
            );
        });

        return $results
            ->sortByDesc(fn (RecommendationResult $result) => $result->score->finalScore)
            ->take($limit)
            ->values()
            ->all();
    }

    private function trendingCounts(int $windowDays): Collection
    {
        $types = [
            InteractionType::Viewed,
            InteractionType::Wishlisted,
            InteractionType::CartAdded,
            InteractionType::Purchased,
        ];

        $combined = [];

        foreach ($types as $type) {
            foreach ($this->repository->interactionCountsByProduct($type, $windowDays) as $productId => $count) {
                $combined[$productId] = ($combined[$productId] ?? 0) + $count;
            }
        }

        return collect($combined);
    }

    private function reasonFor(Product $product, float $trending, float $newArrival, float $rating, float $wishlisted): string
    {
        return match (true) {
            $product->is_featured => 'Featured pick',
            $trending > 0.5 => 'Trending this week',
            $newArrival >= 1.0 => 'New arrival',
            $rating >= 0.8 => 'Highly rated',
            $wishlisted > 0.5 => 'Popular on wishlists',
            default => 'Popular right now',
        };
    }
}
