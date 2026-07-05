<?php

namespace App\Services\Recommendation;

use App\DTOs\RecommendationResult;
use App\Models\Product;
use App\Models\User;
use App\Repositories\RecommendationRepository;
use Illuminate\Support\Facades\Log;

/**
 * The only recommendation class controllers/views should depend on. Wires
 * together the three algorithms + the hybrid blend behind a caching layer,
 * and is the single place that writes to the `recommendations` log channel
 * (execution time, algorithm used, result count, cache hit/miss) so every
 * generation event is observable without instrumenting each algorithm.
 */
class RecommendationService
{
    public function __construct(
        private readonly HybridRecommendationService $hybrid,
        private readonly ContentBasedService $content,
        private readonly CollaborativeFilteringService $collaborative,
        private readonly PopularityService $popularity,
        private readonly RecommendationCacheService $cache,
        private readonly RecommendationRepository $repository,
        private readonly InteractionTrackingService $tracking,
    ) {
    }

    /**
     * "Recommended For You" — the hybrid blend, cached per user.
     *
     * @return RecommendationResult[]
     */
    public function forYou(User $user, ?int $limit = null): array
    {
        $limit = $limit ?? (int) config('recommendation.recommendation_limit', 12);
        $baseKey = "hybrid:{$limit}";

        return $this->timed('hybrid', $user->id, fn () => $this->cache->remember(
            $baseKey,
            $user->id,
            fn () => $this->hybrid->recommendForUser($user, $limit),
        ), $this->cache->has($baseKey, $user->id));
    }

    /**
     * "Similar Products" on a product detail page — content-based item-item
     * similarity, cached per product (no user context needed).
     *
     * @return RecommendationResult[]
     */
    public function similarProducts(Product $product, int $limit = 6): array
    {
        $baseKey = "similar:{$product->id}:{$limit}";

        return $this->timed('content_similar', null, fn () => $this->cache->remember(
            $baseKey,
            null,
            fn () => $this->content->similarProducts($product, $limit),
        ), $this->cache->has($baseKey, null));
    }

    /**
     * Global trending fallback — no personalization, cached catalogue-wide.
     *
     * @return RecommendationResult[]
     */
    public function trending(int $limit = 8): array
    {
        $baseKey = "trending:{$limit}";

        return $this->timed('popularity', null, fn () => $this->cache->remember(
            $baseKey,
            null,
            fn () => $this->popularity->recommend($limit),
        ), $this->cache->has($baseKey, null));
    }

    /**
     * Runs a single named algorithm in isolation — bypasses the hybrid blend
     * and the cache entirely. Exists for academic/algorithm comparison, not
     * for end-user facing pages.
     *
     * @return RecommendationResult[]
     */
    public function algorithmOnly(User $user, string $algorithm, ?int $limit = null): array
    {
        $limit = $limit ?? (int) config('recommendation.recommendation_limit', 12);

        return $this->timed($algorithm, $user->id, fn () => match ($algorithm) {
            'content' => $this->content->recommendForUser($user, $limit),
            'collaborative' => $this->collaborative->recommendForUser($user, $limit),
            'popularity' => $this->popularity->recommend($limit, $this->repository->excludedProductIds($user->id)),
            'hybrid' => $this->hybrid->recommendForUser($user, $limit),
            default => throw new \InvalidArgumentException("Unknown algorithm: {$algorithm}"),
        }, cacheHit: false);
    }

    /**
     * Persists one RecommendationLog row per result shown, for click-through
     * tracking and offline evaluation.
     *
     * @param  RecommendationResult[]  $results
     */
    public function logShown(User $user, array $results, string $module): void
    {
        foreach ($results as $result) {
            $this->repository->logRecommendationShown($user->id, $result->product, $module, $result->score);
        }
    }

    /**
     * Records that a recommended product was clicked — both as a positive
     * behavioural signal for future recommendations, and as a click-through
     * mark on the RecommendationLog row it was shown in.
     */
    public function recordClick(User $user, Product $product, string $module): void
    {
        $this->tracking->recordRecommendationClick($user, $product, $module);
        $this->repository->markRecommendationClicked($user->id, $product->id, $module);
    }

    /**
     * @return RecommendationResult[]
     */
    private function timed(string $algorithm, ?int $userId, \Closure $callback, bool $cacheHit): array
    {
        $start = microtime(true);
        $results = $callback();
        $elapsedMs = (microtime(true) - $start) * 1000;

        Log::channel('recommendations')->info('Recommendation generated', [
            'algorithm' => $algorithm,
            'user_id' => $userId,
            'count' => count($results),
            'execution_time_ms' => round($elapsedMs, 2),
            'cache_hit' => $cacheHit,
        ]);

        return $results;
    }
}
