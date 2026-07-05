<?php

namespace App\Services\Recommendation;

use App\DTOs\RecommendationResult;
use App\DTOs\RecommendationScore;
use App\Models\User;
use App\Repositories\RecommendationRepository;
use App\Services\ProductCatalogueService;
use Illuminate\Support\Collection;

/**
 * Recommends products liked by users with similar behaviour — deterministic
 * Jaccard similarity over interacted-product sets, not a trained model.
 * Designed to degrade to an empty result (never an exception) when there
 * isn't enough data to compare against, so cold-start and small datasets
 * are handled the same way: gracefully.
 */
class CollaborativeFilteringService
{
    public function __construct(
        private readonly RecommendationRepository $repository,
        private readonly ProductCatalogueService $catalogue,
    ) {
    }

    /**
     * @return RecommendationResult[]
     */
    public function recommendForUser(User $user, int $limit = 12): array
    {
        $userProducts = $this->repository->positiveInteractionsForUser($user->id)
            ->pluck('product_id')
            ->filter()
            ->unique();

        if ($userProducts->isEmpty()) {
            return [];
        }

        $otherUserSets = $this->repository->positiveProductSetsByUser($user->id);

        if ($otherUserSets->isEmpty()) {
            return [];
        }

        $threshold = (float) config('recommendation.collaborative.similarity_threshold', 0.05);
        $maxSimilarUsers = (int) config('recommendation.collaborative.max_similar_users', 20);

        $similarities = $otherUserSets
            ->map(fn (Collection $otherProducts) => $this->jaccard($userProducts, $otherProducts))
            ->filter(fn (float $similarity) => $similarity >= $threshold)
            ->sortDesc()
            ->take($maxSimilarUsers);

        if ($similarities->isEmpty()) {
            return [];
        }

        $excluded = $this->repository->excludedProductIds($user->id);
        $candidateScores = [];

        foreach ($similarities as $otherUserId => $similarity) {
            foreach ($otherUserSets->get($otherUserId, collect()) as $productId) {
                if (in_array($productId, $excluded, true)) {
                    continue;
                }

                $candidateScores[$productId] = ($candidateScores[$productId] ?? 0) + $similarity;
            }
        }

        if (empty($candidateScores)) {
            return [];
        }

        $maxScore = max($candidateScores);
        $confidence = min(1.0, $similarities->count() / 5);
        $topSimilarity = round($similarities->first() * 100);

        $products = $this->catalogue->query()
            ->whereIn('products.id', array_keys($candidateScores))
            ->get()
            ->keyBy('id');

        $results = collect($candidateScores)
            ->map(function (float $rawScore, int $productId) use ($products, $maxScore, $confidence, $topSimilarity) {
                $product = $products->get($productId);

                if (! $product) {
                    return null;
                }

                $normalized = $maxScore > 0 ? $rawScore / $maxScore : 0.0;
                $reason = "Popular among shoppers with similar taste ({$topSimilarity}% match)";

                return new RecommendationResult(
                    product: $product,
                    score: new RecommendationScore(
                        contentScore: 0.0,
                        collaborativeScore: $normalized,
                        popularityScore: 0.0,
                        finalScore: $normalized,
                        confidence: $confidence,
                        reason: $reason,
                        algorithmSource: 'collaborative',
                        generatedAt: new \DateTimeImmutable(),
                    ),
                );
            })
            ->filter()
            ->sortByDesc(fn (RecommendationResult $result) => $result->score->finalScore)
            ->take($limit)
            ->values();

        return $results->all();
    }

    /**
     * @param  Collection<int, int>  $a
     * @param  Collection<int, int>  $b
     */
    private function jaccard(Collection $a, Collection $b): float
    {
        if ($a->isEmpty() || $b->isEmpty()) {
            return 0.0;
        }

        $intersection = $a->intersect($b)->count();
        $union = $a->merge($b)->unique()->count();

        return $union > 0 ? $intersection / $union : 0.0;
    }
}
