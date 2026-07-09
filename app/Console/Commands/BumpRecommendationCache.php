<?php

namespace App\Console\Commands;

use App\Services\Recommendation\RecommendationCacheService;
use Illuminate\Console\Command;

/**
 * RecommendationCacheService::bumpGlobalVersion() is already wired into every
 * *data* mutation that should invalidate cached recommendations (cart/wishlist
 * changes, product edits, checkout). It is NOT wired into anything when the
 * *scoring/gating code itself* changes (e.g. a ContentBasedService rule, a
 * Product::scopeXCompatible() gate, a COMPLEMENT_TYPES mapping) — Laravel has
 * no way to detect that automatically. Without a manual bump, up to 30
 * minutes of pre-change cached results (config('recommendation.cache_minutes'))
 * keep being served after a logic fix ships, which is exactly what happened
 * after the age_group gating fix. Run this after any such change.
 */
class BumpRecommendationCache extends Command
{
    protected $signature   = 'recommendations:bump-cache';
    protected $description = 'Invalidate all cached recommendation results (run after changing scoring/gating logic)';

    public function handle(RecommendationCacheService $cache): int
    {
        $cache->bumpGlobalVersion();

        $this->info('Recommendation cache global version bumped — all previously cached results are now unreachable.');

        return self::SUCCESS;
    }
}
