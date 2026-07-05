<?php

namespace App\DTOs;

/**
 * Full scoring detail behind one recommended product. End-user UI only
 * ever needs `reason` (see RecommendationResult) — everything else here
 * exists for debugging, logging, and offline evaluation.
 */
final class RecommendationScore
{
    public function __construct(
        public readonly float $contentScore,
        public readonly float $collaborativeScore,
        public readonly float $popularityScore,
        public readonly float $finalScore,
        public readonly float $confidence,
        public readonly string $reason,
        public readonly string $algorithmSource,
        public readonly \DateTimeImmutable $generatedAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'content_score' => round($this->contentScore, 4),
            'collaborative_score' => round($this->collaborativeScore, 4),
            'popularity_score' => round($this->popularityScore, 4),
            'final_score' => round($this->finalScore, 4),
            'confidence' => round($this->confidence, 4),
            'reason' => $this->reason,
            'algorithm_source' => $this->algorithmSource,
            'generated_at' => $this->generatedAt->format(DATE_ATOM),
        ];
    }
}
