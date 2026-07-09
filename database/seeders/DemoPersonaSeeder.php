<?php

namespace Database\Seeders;

use App\Enums\InteractionType;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\UserInteraction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Track A Part 2-4 — Demo Persona Seeder
 *
 * Creates three named buyer personas with distinct shopping profiles,
 * plus peer groups and noise users that give the collaborative-filtering
 * engine real Jaccard similarity signal to work with.
 *
 * PERSONAS
 *   Persona A  athlete@demo.com         — Sports / Solstice Active performance gear
 *   Persona B  professional@demo.com    — Men Formal (Northfield) + Women Classic (Verona Row)
 *   Persona C  budget@demo.com          — Kids Casual (Bright Sprout), KES 500-2,000
 *
 * PEERS (3 per persona A/B, 2 for C)
 *   peer.athlete.1-3@demo.com, peer.prof.1-3@demo.com, peer.budget.1-2@demo.com
 *   Each peer shares ~50-70% of the persona's positive product set (Jaccard >> 0.05 threshold).
 *
 * NOISE USERS (2)
 *   noise.1-2@demo.com  — 3-5 scattered interactions across random categories
 *
 * Safe to re-run: if a persona user already has UserInteraction rows the
 * whole persona block is skipped, so the unique review constraint is never
 * hit twice.
 *
 * All passwords: password123
 */
class DemoPersonaSeeder extends Seeder
{
    public function run(): void
    {
        // ── Personas ──────────────────────────────────────────────────────────
        $athlete      = $this->user('The Athlete',       'athlete@demo.com');
        $professional = $this->user('The Professional',  'professional@demo.com');
        $budget       = $this->user('The Budget Shopper','budget@demo.com');

        // ── Peers ─────────────────────────────────────────────────────────────
        $athletePeers = [
            $this->user('Athlete Peer 1', 'peer.athlete.1@demo.com'),
            $this->user('Athlete Peer 2', 'peer.athlete.2@demo.com'),
            $this->user('Athlete Peer 3', 'peer.athlete.3@demo.com'),
        ];
        $profPeers = [
            $this->user('Professional Peer 1', 'peer.prof.1@demo.com'),
            $this->user('Professional Peer 2', 'peer.prof.2@demo.com'),
            $this->user('Professional Peer 3', 'peer.prof.3@demo.com'),
        ];
        $budgetPeers = [
            $this->user('Budget Peer 1', 'peer.budget.1@demo.com'),
            $this->user('Budget Peer 2', 'peer.budget.2@demo.com'),
        ];

        // ── Noise users ───────────────────────────────────────────────────────
        $noise1 = $this->user('Noise User 1', 'noise.1@demo.com');
        $noise2 = $this->user('Noise User 2', 'noise.2@demo.com');

        // ── Product pools ─────────────────────────────────────────────────────
        // Primary clusters (added by CatalogueExpansionSeeder)
        $sportsCluster  = $this->products('sports',  'Solstice Active', 'Performance');
        $menCluster     = $this->products('men',     'Northfield & Co', 'Formal');
        $womenCluster   = $this->products('women',   'Verona Row',      'Classic');
        $kidsCluster    = $this->products('kids',    'Bright Sprout',   'Casual');

        // Supplementary pools (existing catalogue products, same category)
        $sportsExtra    = $this->productsByCategory('sports',  $sportsCluster->pluck('id')->toArray(), 20);
        $menExtra       = $this->productsByCategory('men',     $menCluster->pluck('id')->toArray(), 15);
        $womenExtra     = $this->productsByCategory('women',   $womenCluster->pluck('id')->toArray(), 15);
        $kidsExtra      = $this->productsByCategory('kids',    $kidsCluster->pluck('id')->toArray(), 15);
        $accessoriesAll = $this->productsByCategory('accessories', [], 15);

        // ── Persona A interactions ────────────────────────────────────────────
        if (! $this->hasInteractions($athlete)) {
            // Purchased all 6 cluster performance items — these build the core
            // content-based profile and are also excluded from future recs.
            $this->interact($athlete, $sportsCluster, InteractionType::Purchased);

            // Deep engagement with existing sports stock
            $viewedSports = $sportsExtra->take(18);
            $this->interact($athlete, $viewedSports, InteractionType::Viewed);
            $this->interact($athlete, $sportsExtra->slice(3, 8), InteractionType::Wishlisted);
            $this->interact($athlete, $sportsExtra->slice(11, 4), InteractionType::CartAdded);
            $this->interact($athlete, $sportsExtra->slice(11, 4), InteractionType::CartRemoved);

            // Light browsing in accessories (noise, not core signal)
            $this->interact($athlete, $accessoriesAll->take(3), InteractionType::Viewed);

            $this->command?->info("  Persona A (Athlete): {$this->interactionCount($athlete)} interactions created.");
            $this->seedPersonaReviews($sportsCluster->merge($sportsExtra->take(6)), 12);
        } else {
            $this->command?->info("  Persona A already seeded — skipping.");
        }

        // ── Persona B interactions ────────────────────────────────────────────
        if (! $this->hasInteractions($professional)) {
            // Purchased a mix from the two formal clusters
            $this->interact($professional, $menCluster, InteractionType::Purchased);
            $this->interact($professional, $womenCluster->take(3), InteractionType::Purchased);

            // Extensive browsing across both formal categories
            $this->interact($professional, $menExtra->take(15), InteractionType::Viewed);
            $this->interact($professional, $womenExtra->take(12), InteractionType::Viewed);
            $this->interact($professional, $menExtra->slice(5, 6), InteractionType::Wishlisted);
            $this->interact($professional, $womenExtra->slice(3, 5), InteractionType::Wishlisted);
            $this->interact($professional, $menExtra->slice(9, 4), InteractionType::CartAdded);
            $this->interact($professional, $womenExtra->slice(7, 3), InteractionType::CartAdded);

            // Light browsing in accessories (bags and jewellery — plausible for professional)
            $this->interact($professional, $accessoriesAll->take(5), InteractionType::Viewed);
            $this->interact($professional, $accessoriesAll->take(2), InteractionType::Wishlisted);

            $this->command?->info("  Persona B (Professional): {$this->interactionCount($professional)} interactions created.");
            $this->seedPersonaReviews($menCluster->merge($womenCluster)->merge($menExtra->take(4)), 12);
        } else {
            $this->command?->info("  Persona B already seeded — skipping.");
        }

        // ── Persona C interactions ────────────────────────────────────────────
        if (! $this->hasInteractions($budget)) {
            // Purchased most of the kids casual cluster (budget-conscious, 4-5 buys)
            $this->interact($budget, $kidsCluster->take(4), InteractionType::Purchased);

            // High view-to-buy ratio — browses a lot, buys selectively
            $this->interact($budget, $kidsExtra->take(15), InteractionType::Viewed);
            $this->interact($budget, $kidsExtra->slice(2, 8), InteractionType::Wishlisted);

            // Cart churn: adds items then removes them (budget hesitation)
            $this->interact($budget, $kidsExtra->slice(5, 6), InteractionType::CartAdded);
            $this->interact($budget, $kidsExtra->slice(5, 4), InteractionType::CartRemoved);

            // Also browses very cheap sports items
            $cheapSports = $sportsExtra->filter(fn ($p) => $p->price < 2000)->take(5);
            $this->interact($budget, $cheapSports, InteractionType::Viewed);

            $this->command?->info("  Persona C (Budget): {$this->interactionCount($budget)} interactions created.");
            $this->seedPersonaReviews($kidsCluster->merge($kidsExtra->take(5)), 8);
        } else {
            $this->command?->info("  Persona C already seeded — skipping.");
        }

        // ── Athlete peers ─────────────────────────────────────────────────────
        // Each peer overlaps ~60-70% of Persona A's cluster purchases + some extra sports
        $athleteCorePids = $sportsCluster->pluck('id');

        foreach ($athletePeers as $i => $peer) {
            if ($this->hasInteractions($peer)) {
                continue;
            }

            // All peers viewed/purchased a majority of the same cluster products
            $clusterSlice = $sportsCluster->skip($i)->take(5);                // 5 of 6 cluster items
            $extraSlice   = $sportsExtra->skip($i * 2)->take(10);             // 10 extra sports items

            $this->interact($peer, $clusterSlice, InteractionType::Purchased);
            $this->interact($peer, $extraSlice, InteractionType::Viewed);
            $this->interact($peer, $extraSlice->take(5), InteractionType::Wishlisted);

            $this->command?->info("  Athlete peer {$peer->email}: {$this->interactionCount($peer)} interactions.");
        }

        // ── Professional peers ────────────────────────────────────────────────
        foreach ($profPeers as $i => $peer) {
            if ($this->hasInteractions($peer)) {
                continue;
            }

            // Mix of men formal + women classic overlapping with Persona B
            $menSlice   = $menCluster->skip($i)->take(4);
            $womenSlice = $womenCluster->skip($i)->take(3);
            $menE       = $menExtra->skip($i * 2)->take(8);
            $womenE     = $womenExtra->skip($i * 2)->take(6);

            $this->interact($peer, $menSlice,   InteractionType::Purchased);
            $this->interact($peer, $womenSlice, InteractionType::Viewed);
            $this->interact($peer, $menE,       InteractionType::Viewed);
            $this->interact($peer, $womenE,     InteractionType::Wishlisted);

            $this->command?->info("  Prof peer {$peer->email}: {$this->interactionCount($peer)} interactions.");
        }

        // ── Budget peers ──────────────────────────────────────────────────────
        foreach ($budgetPeers as $i => $peer) {
            if ($this->hasInteractions($peer)) {
                continue;
            }

            $kidsSlice = $kidsCluster->skip($i)->take(4);
            $kidsE     = $kidsExtra->skip($i * 3)->take(10);

            $this->interact($peer, $kidsSlice, InteractionType::Purchased);
            $this->interact($peer, $kidsE,     InteractionType::Viewed);
            $this->interact($peer, $kidsE->take(5), InteractionType::CartAdded);

            $this->command?->info("  Budget peer {$peer->email}: {$this->interactionCount($peer)} interactions.");
        }

        // ── Noise users ───────────────────────────────────────────────────────
        if (! $this->hasInteractions($noise1)) {
            $this->interact($noise1, $accessoriesAll->take(3), InteractionType::Viewed);
            $this->interact($noise1, $sportsExtra->take(2), InteractionType::Viewed);
            $this->command?->info("  Noise 1: {$this->interactionCount($noise1)} interactions.");
        }

        if (! $this->hasInteractions($noise2)) {
            $this->interact($noise2, $kidsExtra->take(2), InteractionType::Viewed);
            $this->interact($noise2, $menExtra->take(2), InteractionType::Viewed);
            $this->interact($noise2, $accessoriesAll->take(1), InteractionType::Wishlisted);
            $this->command?->info("  Noise 2: {$this->interactionCount($noise2)} interactions.");
        }

        $this->command?->info('DemoPersonaSeeder complete.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function user(string $name, string $email): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => $name,
                'password' => Hash::make('password123'),
                'role'     => 'buyer',
                'status'   => 'active',
            ]
        );
    }

    private function products(string $categorySlug, string $brandName, string $style): \Illuminate\Database\Eloquent\Collection
    {
        return Product::whereHas('category', fn ($q) => $q->where('slug', $categorySlug))
            ->whereHas('brand', fn ($q) => $q->where('name', $brandName))
            ->where('style', $style)
            ->where('status', 'published')
            ->get();
    }

    private function productsByCategory(string $categorySlug, array $excludeIds, int $limit): \Illuminate\Database\Eloquent\Collection
    {
        $query = Product::whereHas('category', fn ($q) => $q->where('slug', $categorySlug))
            ->where('status', 'published');

        if (! empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query->inRandomOrder()->limit($limit)->get();
    }

    private function interact(User $user, $products, InteractionType $type): void
    {
        foreach ($products as $product) {
            UserInteraction::create([
                'user_id'          => $user->id,
                'product_id'       => $product->id,
                'interaction_type' => $type->value,
                'weight'           => $type->defaultWeight(),
            ]);
        }
    }

    private function hasInteractions(User $user): bool
    {
        return UserInteraction::where('user_id', $user->id)->exists();
    }

    private function interactionCount(User $user): int
    {
        return UserInteraction::where('user_id', $user->id)->count();
    }

    /**
     * Seeds 30-40 reviews across the persona's product pool with skewed
     * ratings — most products rated 4-5 stars, a few rated 2-3 for realism.
     * Uses firstOrCreate so re-runs never violate the (product_id, user_id)
     * unique constraint.
     */
    private function seedPersonaReviews($products, int $target): void
    {
        $allReviewers  = $this->reviewerAccounts();
        $ratings       = [5, 5, 4, 5, 4, 3, 5, 4, 2, 5, 4, 5];
        $comments      = [
            'Absolutely love this — fits perfectly and looks great.',
            'High quality for the price, very happy.',
            'Great product, exactly as described.',
            'Really good value. Would buy again.',
            'Decent quality but sizing runs a little large.',
            'Nice item, arrived quickly and well-packaged.',
            'Looks even better in person than in the photos.',
            'Good purchase overall, my family is happy.',
            'Reasonable quality but not quite what I expected.',
            'Brilliant — will definitely order from here again.',
            null,
            null,
        ];

        $reviewCount = 0;

        foreach ($products->take(20) as $idx => $product) {
            // 1-3 reviews per product, spread across reviewer accounts
            $perProduct = ($idx % 3 === 0) ? 3 : (($idx % 3 === 1) ? 2 : 1);

            for ($r = 0; $r < $perProduct && $reviewCount < $target; $r++) {
                $reviewer = $allReviewers[($idx * 3 + $r) % count($allReviewers)];
                Review::firstOrCreate(
                    ['product_id' => $product->id, 'user_id' => $reviewer->id],
                    [
                        'rating'  => $ratings[($idx + $r) % count($ratings)],
                        'comment' => $comments[($idx + $r) % count($comments)],
                    ]
                );
                $reviewCount++;
            }
        }
    }

    private function reviewerAccounts(): array
    {
        static $cache = null;
        if ($cache === null) {
            $cache = User::where('email', 'like', 'reviewer%@example.com')
                ->orderBy('id')
                ->get()
                ->all();
        }

        return $cache;
    }
}
