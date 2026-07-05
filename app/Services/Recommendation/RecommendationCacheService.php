<?php

namespace App\Services\Recommendation;

use Illuminate\Support\Facades\Cache;

/**
 * Wraps Laravel's Cache facade with recommendation-specific key/versioning
 * logic. The app's cache driver is the database store (see config/cache.php,
 * driven by CACHE_STORE=database), which does not support Cache::tags() —
 * that only works with Redis/Memcached. Versioned keys are the portable
 * alternative: bumping a version number makes old keys unreachable without
 * needing to enumerate or delete them, and it works on any cache driver.
 */
class RecommendationCacheService
{
    private const GLOBAL_VERSION_KEY = 'recommendation_cache:global_version';

    private const USER_VERSION_PREFIX = 'recommendation_cache:user_version:';

    public function remember(string $baseKey, ?int $userId, \Closure $callback): mixed
    {
        $minutes = (int) config('recommendation.cache_minutes', 30);

        return Cache::remember($this->buildKey($baseKey, $userId), now()->addMinutes($minutes), $callback);
    }

    /**
     * Checked before remember() so callers can log whether a request was a
     * cache hit or miss — Cache::remember() itself doesn't expose that.
     */
    public function has(string $baseKey, ?int $userId): bool
    {
        return Cache::has($this->buildKey($baseKey, $userId));
    }

    /**
     * Call when a specific user's signals change: wishlist, cart, ratings,
     * or their own preferences. Only invalidates that one user's cached
     * recommendation sets.
     */
    public function forgetForUser(int $userId): void
    {
        $key = self::USER_VERSION_PREFIX.$userId;
        Cache::forever($key, ((int) Cache::get($key, 1)) + 1);
    }

    /**
     * Call when something that could affect *any* user's recommendations
     * changes catalogue-wide — a product goes out of stock, gets archived,
     * or is deleted. Invalidates every cached recommendation set at once.
     */
    public function bumpGlobalVersion(): void
    {
        Cache::forever(self::GLOBAL_VERSION_KEY, ((int) Cache::get(self::GLOBAL_VERSION_KEY, 1)) + 1);
    }

    private function buildKey(string $baseKey, ?int $userId): string
    {
        $globalVersion = (int) Cache::get(self::GLOBAL_VERSION_KEY, 1);
        $segment = "recommendations:v{$globalVersion}:{$baseKey}";

        if ($userId === null) {
            return $segment;
        }

        $userVersion = (int) Cache::get(self::USER_VERSION_PREFIX.$userId, 1);

        return "{$segment}:user{$userId}:uv{$userVersion}";
    }
}
