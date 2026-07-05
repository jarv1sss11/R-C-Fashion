<?php

namespace App\DTOs;

use App\Models\Product;

/**
 * A single recommended product paired with its score. Blade templates
 * should only ever touch `product` and `reason()` — the rest of the score
 * detail is for logging/evaluation, not display.
 */
final class RecommendationResult
{
    public function __construct(
        public readonly Product $product,
        public readonly RecommendationScore $score,
    ) {
    }

    public function reason(): string
    {
        return $this->score->reason;
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'score' => $this->score->toArray(),
        ];
    }
}
