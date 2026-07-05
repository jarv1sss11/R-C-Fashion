<?php

namespace App\Services\Recommendation;

use App\DTOs\RecommendationResult;
use App\DTOs\RecommendationScore;
use App\Models\User;
use App\Repositories\RecommendationRepository;

/**
 * Combines Content-Based, Collaborative, and Popularity output using the
 * weights in config/recommendation.php — without knowing how any of the
 * three actually compute their scores. If an algorithm returns nothing for
 * a user (e.g. Collaborative has no peers to compare against), its weight
 * is redistributed proportionally among whatever did return results,
 * rather than leaving a gap in the blend or hardcoding a "new user" branch.
 */
class HybridRecommendationService
{
    public function __construct(
        private readonly ContentBasedService $content,
        private readonly CollaborativeFilteringService $collaborative,
        private readonly PopularityService $popularity,
        private readonly RecommendationRepository $repository,
    ) {
    }

    /**
     * @return RecommendationResult[]
     */
    public function recommendForUser(User $user, int $limit = 12): array
    {
        $enabled = config('recommendation.enabled');
        $baseWeights = config('recommendation.weights');

        /** @var RecommendationResult[] $contentResults */
        $contentResults = $enabled['content'] ? $this->content->recommendForUser($user, $limit * 2) : [];
        /** @var RecommendationResult[] $collaborativeResults */
        $collaborativeResults = $enabled['collaborative'] ? $this->collaborative->recommendForUser($user, $limit * 2) : [];

        $alreadySurfaced = array_unique(array_merge(
            array_map(fn (RecommendationResult $r) => $r->product->id, $contentResults),
            array_map(fn (RecommendationResult $r) => $r->product->id, $collaborativeResults),
        ));
        $excluded = array_unique(array_merge($this->repository->excludedProductIds($user->id), $alreadySurfaced));

        /** @var RecommendationResult[] $popularityResults */
        $popularityResults = $enabled['popularity'] ? $this->popularity->recommend($limit * 2, $excluded) : [];

        $activeWeights = array_filter([
            'content' => empty($contentResults) ? 0.0 : $baseWeights['content'],
            'collaborative' => empty($collaborativeResults) ? 0.0 : $baseWeights['collaborative'],
            'popularity' => empty($popularityResults) ? 0.0 : $baseWeights['popularity'],
        ]);

        $totalActiveWeight = array_sum($activeWeights);

        if ($totalActiveWeight <= 0) {
            return [];
        }

        $normalizedWeights = array_map(fn ($weight) => $weight / $totalActiveWeight, $activeWeights);

        $confidence = ($normalizedWeights['content'] ?? 0) * $this->firstConfidence($contentResults)
            + ($normalizedWeights['collaborative'] ?? 0) * $this->firstConfidence($collaborativeResults)
            + ($normalizedWeights['popularity'] ?? 0) * $this->firstConfidence($popularityResults);

        $merged = $this->mergeByProduct($contentResults, $collaborativeResults, $popularityResults);

        $blended = array_map(function (array $entry) use ($normalizedWeights, $confidence) {
            $finalScore = ($normalizedWeights['content'] ?? 0) * $entry['content']
                + ($normalizedWeights['collaborative'] ?? 0) * $entry['collaborative']
                + ($normalizedWeights['popularity'] ?? 0) * $entry['popularity'];

            return new RecommendationResult(
                product: $entry['product'],
                score: new RecommendationScore(
                    contentScore: $entry['content'],
                    collaborativeScore: $entry['collaborative'],
                    popularityScore: $entry['popularity'],
                    finalScore: $finalScore,
                    confidence: $confidence,
                    reason: $entry['reason'],
                    algorithmSource: 'hybrid',
                    generatedAt: new \DateTimeImmutable(),
                ),
            );
        }, $merged);

        usort($blended, fn (RecommendationResult $a, RecommendationResult $b) => $b->score->finalScore <=> $a->score->finalScore);

        return array_slice($blended, 0, $limit);
    }

    /**
     * @param  RecommendationResult[]  $contentResults
     * @param  RecommendationResult[]  $collaborativeResults
     * @param  RecommendationResult[]  $popularityResults
     * @return array<int, array{product: \App\Models\Product, content: float, collaborative: float, popularity: float, reason: string}>
     */
    private function mergeByProduct(array $contentResults, array $collaborativeResults, array $popularityResults): array
    {
        $merged = [];

        foreach ($contentResults as $result) {
            $id = $result->product->id;
            $merged[$id] ??= $this->emptyEntry($result->product, $result->reason());
            $merged[$id]['content'] = $result->score->contentScore;
        }

        foreach ($collaborativeResults as $result) {
            $id = $result->product->id;
            $merged[$id] ??= $this->emptyEntry($result->product, $result->reason());
            $merged[$id]['collaborative'] = $result->score->collaborativeScore;
        }

        foreach ($popularityResults as $result) {
            $id = $result->product->id;
            $merged[$id] ??= $this->emptyEntry($result->product, $result->reason());
            $merged[$id]['popularity'] = $result->score->popularityScore;
        }

        return $merged;
    }

    private function emptyEntry($product, string $reason): array
    {
        return [
            'product' => $product,
            'content' => 0.0,
            'collaborative' => 0.0,
            'popularity' => 0.0,
            'reason' => $reason,
        ];
    }

    /**
     * @param  RecommendationResult[]  $results
     */
    private function firstConfidence(array $results): float
    {
        return $results[0]->score->confidence ?? 0.0;
    }
}
