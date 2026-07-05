<?php

namespace App\Services\Recommendation;

use App\Enums\InteractionType;
use App\Models\User;
use App\Models\UserInteraction;
use App\Repositories\RecommendationRepository;
use App\Services\ProductCatalogueService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Offline evaluation via leave-one-out: each eligible user's single most
 * recent positive interaction is temporarily removed inside a DB transaction
 * that's always rolled back, recommendations are generated as if that
 * interaction never happened, and we check whether the held-out product
 * reappears in the top-K. This is the standard leave-one-out protocol used
 * in recommender-systems literature — it needs no changes to the algorithm
 * services themselves and never persists a side effect.
 */
class RecommendationEvaluator
{
    private const ALGORITHMS = ['content', 'collaborative', 'popularity', 'hybrid'];

    public function __construct(
        private readonly ContentBasedService $content,
        private readonly CollaborativeFilteringService $collaborative,
        private readonly PopularityService $popularity,
        private readonly HybridRecommendationService $hybrid,
        private readonly RecommendationRepository $repository,
        private readonly ProductCatalogueService $catalogue,
    ) {
    }

    public function evaluate(string $algorithm, int $k = 10): array
    {
        if (! in_array($algorithm, self::ALGORITHMS, true)) {
            throw new \InvalidArgumentException("Unknown algorithm: {$algorithm}");
        }

        $users = $this->eligibleUsers();
        $catalogueSize = $this->catalogue->query()->count();

        $precisions = [];
        $recalls = [];
        $averagePrecisions = [];
        $ndcgs = [];
        $recommendedSets = [];

        foreach ($users as $user) {
            [$heldOutProductId, $results] = $this->leaveOneOutTrial($user, $algorithm, $k);

            if ($heldOutProductId === null) {
                continue;
            }

            $recommendedIds = array_map(fn ($result) => $result->product->id, $results);
            $rank = array_search($heldOutProductId, $recommendedIds, true);
            $hit = $rank !== false;

            $precisions[] = $hit ? 1 / $k : 0.0;
            $recalls[] = $hit ? 1.0 : 0.0;
            $averagePrecisions[] = $hit ? 1 / ($rank + 1) : 0.0;
            $ndcgs[] = $hit ? 1 / log($rank + 2, 2) : 0.0;
            $recommendedSets[] = $results;
        }

        $trials = count($precisions);

        if ($trials === 0) {
            return $this->emptyReport($algorithm, $k);
        }

        return [
            'algorithm' => $algorithm,
            'k' => $k,
            'users_evaluated' => $trials,
            'precision_at_k' => round(array_sum($precisions) / $trials, 4),
            'recall_at_k' => round(array_sum($recalls) / $trials, 4),
            'map_at_k' => round(array_sum($averagePrecisions) / $trials, 4),
            'ndcg_at_k' => round(array_sum($ndcgs) / $trials, 4),
            'coverage' => round($this->coverage($recommendedSets, $catalogueSize), 4),
            'diversity' => round($this->diversity($recommendedSets), 4),
            'novelty' => round($this->novelty($recommendedSets), 4),
        ];
    }

    /**
     * Users with at least 2 distinct positively-interacted products — one to
     * hold out as ground truth, at least one left behind as signal.
     */
    private function eligibleUsers(): Collection
    {
        return User::all()->filter(function (User $user) {
            return $this->repository->positiveInteractionsForUser($user->id)
                ->pluck('product_id')
                ->filter()
                ->unique()
                ->count() >= 2;
        })->values();
    }

    /**
     * @return array{0: int|null, 1: \App\DTOs\RecommendationResult[]}
     */
    private function leaveOneOutTrial(User $user, string $algorithm, int $k): array
    {
        $heldOutProductId = $this->repository->positiveInteractionsForUser($user->id)
            ->filter(fn ($interaction) => $interaction->product_id !== null)
            ->sortByDesc('created_at')
            ->pluck('product_id')
            ->unique()
            ->first();

        if ($heldOutProductId === null) {
            return [null, []];
        }

        $results = [];

        DB::beginTransaction();

        try {
            // Remove every interaction row for this product, not just the
            // most recent one — a product can have several (viewed AND
            // wishlisted), and a leftover row would still surface it via
            // excludedProductIds(), permanently disqualifying it from a hit.
            UserInteraction::where('user_id', $user->id)->where('product_id', $heldOutProductId)->delete();

            $results = match ($algorithm) {
                'content' => $this->content->recommendForUser($user, $k),
                'collaborative' => $this->collaborative->recommendForUser($user, $k),
                'popularity' => $this->popularity->recommend($k, $this->repository->excludedProductIds($user->id)),
                'hybrid' => $this->hybrid->recommendForUser($user, $k),
            };
        } finally {
            DB::rollBack();
        }

        return [$heldOutProductId, $results];
    }

    /**
     * @param  array<int, \App\DTOs\RecommendationResult[]>  $recommendedSets
     */
    private function coverage(array $recommendedSets, int $catalogueSize): float
    {
        if ($catalogueSize === 0) {
            return 0.0;
        }

        $recommendedIds = [];

        foreach ($recommendedSets as $results) {
            foreach ($results as $result) {
                $recommendedIds[$result->product->id] = true;
            }
        }

        return count($recommendedIds) / $catalogueSize;
    }

    /**
     * Average intra-list diversity: the share of same-list product pairs
     * that fall in different categories, averaged across all evaluated users.
     *
     * @param  array<int, \App\DTOs\RecommendationResult[]>  $recommendedSets
     */
    private function diversity(array $recommendedSets): float
    {
        $listScores = [];

        foreach ($recommendedSets as $results) {
            $count = count($results);

            if ($count < 2) {
                continue;
            }

            $pairs = 0;
            $differentCategoryPairs = 0;

            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $pairs++;

                    if ($results[$i]->product->category_id !== $results[$j]->product->category_id) {
                        $differentCategoryPairs++;
                    }
                }
            }

            $listScores[] = $differentCategoryPairs / $pairs;
        }

        return $listScores ? array_sum($listScores) / count($listScores) : 0.0;
    }

    /**
     * Average unpopularity of recommended items (1 - normalized view count):
     * higher means the algorithm surfaces less mainstream products.
     *
     * @param  array<int, \App\DTOs\RecommendationResult[]>  $recommendedSets
     */
    private function novelty(array $recommendedSets): float
    {
        $viewCounts = $this->repository->interactionCountsByProduct(InteractionType::Viewed);
        $max = $viewCounts->isEmpty() ? 0 : $viewCounts->max();

        $scores = [];

        foreach ($recommendedSets as $results) {
            foreach ($results as $result) {
                $ratio = $max > 0 ? ($viewCounts->get($result->product->id, 0) / $max) : 0.0;
                $scores[] = 1 - $ratio;
            }
        }

        return $scores ? array_sum($scores) / count($scores) : 0.0;
    }

    private function emptyReport(string $algorithm, int $k): array
    {
        return [
            'algorithm' => $algorithm,
            'k' => $k,
            'users_evaluated' => 0,
            'precision_at_k' => 0.0,
            'recall_at_k' => 0.0,
            'map_at_k' => 0.0,
            'ndcg_at_k' => 0.0,
            'coverage' => 0.0,
            'diversity' => 0.0,
            'novelty' => 0.0,
        ];
    }
}
