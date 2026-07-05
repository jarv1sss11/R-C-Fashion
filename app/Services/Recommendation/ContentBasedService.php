<?php

namespace App\Services\Recommendation;

use App\DTOs\RecommendationResult;
use App\DTOs\RecommendationScore;
use App\Models\Product;
use App\Models\User;
use App\Models\UserInteraction;
use App\Repositories\RecommendationRepository;
use App\Services\ProductCatalogueService;
use Illuminate\Support\Collection;

/**
 * Recommends products by matching a user's own behaviour (categories,
 * colours, price range they've engaged with) against the catalogue.
 * The only algorithm that works for a brand-new user with zero peers to
 * compare against — everything here is derived from one person's own data.
 */
class ContentBasedService
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
        $interactions = $this->repository->positiveInteractionsForUser($user->id)
            ->filter(fn (UserInteraction $interaction) => $interaction->product_id !== null);

        if ($interactions->isEmpty()) {
            // Deliberately empty, not a guess — HybridRecommendationService
            // treats this as "no content signal" and leans on Popularity.
            return [];
        }

        $profile = $this->buildProfile($interactions);
        $excluded = $this->repository->excludedProductIds($user->id);

        $candidates = $this->catalogue->query()
            ->whereNotIn('products.id', $excluded ?: [0])
            ->get();

        $confidence = min(1.0, $interactions->count() / 10);

        $scored = $candidates->map(function (Product $product) use ($profile, $confidence) {
            [$score, $reason] = $this->scoreProduct($product, $profile);

            return $this->toResult($product, $score, $reason, $confidence);
        })->filter(fn (RecommendationResult $result) => $result->score->contentScore > 0);

        return $this->diversify($scored, $limit);
    }

    /**
     * Item-item similarity for "Similar Products" on a product detail page —
     * no user involved, purely product-to-product.
     *
     * @return RecommendationResult[]
     */
    public function similarProducts(Product $product, int $limit = 6): array
    {
        $candidates = $this->catalogue->query()
            ->where('products.id', '!=', $product->id)
            ->where('products.category_id', $product->category_id)
            ->get();

        $scored = $candidates->map(function (Product $candidate) use ($product) {
            $score = 0.4; // same category already guaranteed by the query above

            if ($candidate->brand_id && $candidate->brand_id === $product->brand_id) {
                $score += 0.15;
            }

            if ($candidate->style && $candidate->style === $product->style) {
                $score += 0.15;
            }

            if ($candidate->primary_color && $candidate->primary_color === $product->primary_color) {
                $score += 0.15;
            }

            $priceDelta = $product->price > 0
                ? abs((float) $candidate->price - (float) $product->price) / (float) $product->price
                : 1.0;
            $score += 0.15 * max(0, 1 - min(1, $priceDelta));

            $reason = "Similar to {$product->name}";

            return $this->toResult($candidate, min(1.0, $score), $reason, 0.8, 'content');
        })->sortByDesc(fn (RecommendationResult $result) => $result->score->finalScore);

        return $scored->take($limit)->values()->all();
    }

    /**
     * @return array{0: float, 1: string}
     */
    private function scoreProduct(Product $product, array $profile): array
    {
        $weights = config('recommendation.content_weights');

        $categoryAffinity = $profile['categories'][$product->category_id] ?? 0.0;
        $brandAffinity = $product->brand_id ? ($profile['brands'][$product->brand_id] ?? 0.0) : 0.0;
        $styleAffinity = $product->style ? ($profile['styles'][$product->style] ?? 0.0) : 0.0;
        $colorAffinity = $product->primary_color ? ($profile['colors'][$product->primary_color] ?? 0.0) : 0.0;
        $priceAffinity = $this->priceAffinity((float) $product->price, $profile['price_min'], $profile['price_max']);
        $tagsAffinity = $this->tagsAffinity($product->tags ?? [], $profile['tags']);
        $ageGroupAffinity = $product->age_group ? ($profile['age_groups'][$product->age_group] ?? 0.0) : 0.0;
        $seasonAffinity = $product->season ? ($profile['seasons'][$product->season] ?? 0.0) : 0.0;

        $score = ($weights['category'] * $categoryAffinity)
            + ($weights['brand'] * $brandAffinity)
            + ($weights['style'] * $styleAffinity)
            + ($weights['color'] * $colorAffinity)
            + ($weights['price'] * $priceAffinity)
            + ($weights['tags'] * $tagsAffinity)
            + ($weights['age_group'] * $ageGroupAffinity)
            + ($weights['season'] * $seasonAffinity);

        $reason = match (true) {
            $categoryAffinity > 0 && $categoryAffinity >= max($brandAffinity, $styleAffinity) => "Because you like {$product->category->name}",
            $brandAffinity > 0 && $product->brand => "Because you shop {$product->brand->name}",
            $styleAffinity > 0 => "Because you like {$product->style} styles",
            $colorAffinity > 0 => "Because you've shown interest in {$product->primary_color} items",
            $priceAffinity > 0 => 'Matches your usual price range',
            default => 'Based on your browsing history',
        };

        return [$score, $reason];
    }

    private function tagsAffinity(array $productTags, array $tagProfile): float
    {
        $productTags = array_filter($productTags);

        if (empty($productTags) || empty($tagProfile)) {
            return 0.0;
        }

        $scores = array_map(fn ($tag) => $tagProfile[$tag] ?? 0.0, $productTags);

        return array_sum($scores) / count($scores);
    }

    private function priceAffinity(float $price, ?float $min, ?float $max): float
    {
        if ($min === null || $max === null) {
            return 0.0;
        }

        if ($price >= $min && $price <= $max) {
            return 1.0;
        }

        $range = max($max - $min, 1);
        $distance = $price < $min ? $min - $price : $price - $max;

        return max(0.0, 1 - ($distance / $range));
    }

    private function buildProfile(Collection $interactions): array
    {
        $categoryWeights = [];
        $brandWeights = [];
        $styleWeights = [];
        $colorWeights = [];
        $tagWeights = [];
        $ageGroupWeights = [];
        $seasonWeights = [];
        $prices = [];

        foreach ($interactions as $interaction) {
            $product = $interaction->product;

            if (! $product) {
                continue;
            }

            $categoryWeights[$product->category_id] = ($categoryWeights[$product->category_id] ?? 0) + $interaction->weight;

            if ($product->brand_id) {
                $brandWeights[$product->brand_id] = ($brandWeights[$product->brand_id] ?? 0) + $interaction->weight;
            }

            if ($product->style) {
                $styleWeights[$product->style] = ($styleWeights[$product->style] ?? 0) + $interaction->weight;
            }

            if ($product->primary_color) {
                $colorWeights[$product->primary_color] = ($colorWeights[$product->primary_color] ?? 0) + $interaction->weight;
            }

            if ($product->age_group) {
                $ageGroupWeights[$product->age_group] = ($ageGroupWeights[$product->age_group] ?? 0) + $interaction->weight;
            }

            if ($product->season) {
                $seasonWeights[$product->season] = ($seasonWeights[$product->season] ?? 0) + $interaction->weight;
            }

            foreach (($product->tags ?? []) as $tag) {
                $tagWeights[$tag] = ($tagWeights[$tag] ?? 0) + $interaction->weight;
            }

            $prices[] = (float) $product->price;
        }

        return [
            'categories' => $this->normalize($categoryWeights),
            'brands' => $this->normalize($brandWeights),
            'styles' => $this->normalize($styleWeights),
            'colors' => $this->normalize($colorWeights),
            'tags' => $this->normalize($tagWeights),
            'age_groups' => $this->normalize($ageGroupWeights),
            'seasons' => $this->normalize($seasonWeights),
            'price_min' => $prices ? min($prices) * 0.7 : null,
            'price_max' => $prices ? max($prices) * 1.3 : null,
        ];
    }

    private function normalize(array $weights): array
    {
        if (empty($weights)) {
            return [];
        }

        $max = max($weights);

        if ($max <= 0) {
            return [];
        }

        return array_map(fn ($value) => max(0, $value) / $max, $weights);
    }

    /**
     * Re-rank so no single category dominates the result set — round-robins
     * across category groups instead of taking the raw top-N by score.
     *
     * @param  Collection<int, RecommendationResult>  $scored
     * @return RecommendationResult[]
     */
    private function diversify(Collection $scored, int $limit): array
    {
        $byCategory = $scored
            ->sortByDesc(fn (RecommendationResult $result) => $result->score->finalScore)
            ->groupBy(fn (RecommendationResult $result) => $result->product->category_id)
            ->map(fn (Collection $group) => $group->values());

        $output = [];
        $round = 0;

        while (count($output) < $limit && $byCategory->contains(fn (Collection $group) => $group->count() > $round)) {
            foreach ($byCategory as $group) {
                if ($group->has($round)) {
                    $output[] = $group->get($round);
                }

                if (count($output) >= $limit) {
                    break;
                }
            }

            $round++;
        }

        return $output;
    }

    private function toResult(Product $product, float $score, string $reason, float $confidence, string $source = 'content'): RecommendationResult
    {
        return new RecommendationResult(
            product: $product,
            score: new RecommendationScore(
                contentScore: $score,
                collaborativeScore: 0.0,
                popularityScore: 0.0,
                finalScore: $score,
                confidence: $confidence,
                reason: $reason,
                algorithmSource: $source,
                generatedAt: new \DateTimeImmutable(),
            ),
        );
    }
}
