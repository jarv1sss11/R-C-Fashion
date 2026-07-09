<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/**
 * One-off backfill for products.product_type, inferred from each product's
 * name (and, for a handful of Sports-department garment words that are
 * ambiguous outside that context, its category) — not new content, purely
 * a classification of data that already exists.
 *
 * Rules are checked in order, first match wins. Order matters: more specific
 * phrases ("football boots", "oxford shoes", "cropped shirt") are checked
 * before the generic words they're built from ("shoes" is not a standalone
 * rule at all — every shoe-like item is caught by a more specific pattern
 * first), and Sports-department garment words (top/tank/tee/bra/jersey/kit,
 * leggings, shorts) are checked before their generic non-sports counterparts
 * so an athletic tee and a fashion tee land in different buckets.
 */
class BackfillProductTypes extends Command
{
    protected $signature   = 'products:backfill-type {--chunk=100 : Products per DB batch}';
    protected $description = 'Infer and backfill products.product_type from each product\'s name';

    /**
     * @var array<int, array{0: string, 1: string}> Ordered [pattern, type] pairs; first match wins.
     *
     * Patterns marked "new" below were added after the initial backfill left
     * 17/264 products unmatched — each is a genuine synonym/style-name for an
     * EXISTING type (e.g. "brogue"/"monk-strap" are dress-shoe styles, same
     * bucket as "oxford shoes"), added as an alternative on the existing rule
     * so ordering/precedence is unchanged. Two products (baby socks, a kids
     * costume cape) had no honest fit in the existing 31-type taxonomy —
     * rather than force them into an unrelated bucket (which would pollute
     * that bucket's Similar-Products/Complete-the-Look candidates with a
     * genuinely unrelated item), they got two new, deliberately thin leaf
     * types ('socks', 'cape') with no complement-mapping entries yet, the
     * same "accepted known limitation" treatment already given to other
     * thin types (waistcoat, belt).
     */
    private const RULES = [
        ['/football boots/i',                       'boots'],
        ['/sneakers|trainers|running shoes/i',       'sneakers'],
        ['/sandals|espadrille/i',                    'sandals'],
        ['/heels|stiletto|\bpumps\b/i',              'heels'], // new: "stiletto"/"pumps" are heel styles
        ['/oxford shoes|mary jane|boat shoes|brogue|monk-strap|monk strap|ballet flat/i', 'shoes'], // new: brogue/monk-strap/ballet flat are dress/flat shoe styles
        ['/hoodie|hooded sweater/i',                 'hoodie'], // new: "hooded sweater" is a hoodie by another name
        ['/blazer/i',                                'blazer'],
        ['/waistcoat/i',                             'waistcoat'],
        ['/jacket/i',                                'jacket'],
        ['/blouse|cropped shirt/i',                  'blouse'],
        ['/shirt/i',                                 'shirt'],
        ['/backpack|handbag|crossbody|duffel bag|\btote\b|\bbag\b/i', 'bag'], // new: "tote" is a bag style
        ['/tee\b|tank top|camisole/i',               'tee'], // new: non-Sports tank tops/camisoles read as casual tees (Sports-department tanks are already caught earlier as sportswear_top)
        ['/skirt/i',                                 'skirt'],
        ['/trousers/i',                              'trousers'],
        ['/gown|dress|wrap set/i',                   'dress'],
        ['/jeans/i',                                 'jeans'],
        ['/jogger/i',                                'joggers'],
        ['/shorts/i',                                'shorts'],
        ['/romper|sleepsuit|onesie/i',               'romper'], // new: "onesie" is a romper by another name
        ['/watch/i',                                 'watch'],
        ['/necklace/i',                              'necklace'],
        ['/earrings/i',                              'earrings'],
        ['/bracelet/i',                               'bracelet'],
        ['/sunglasses/i',                            'sunglasses'],
        ['/belt/i',                                  'belt'],
        ['/wallet/i',                                'wallet'],
        ['/scarf/i',                                 'scarf'],
        ['/beanie|baseball cap|wide-brim hat|headband|\bhat\b|fedora|trucker cap|five-panel cap|\bcap\b/i', 'hat'], // new: fedora/trucker cap/five-panel cap/generic "cap" are hat styles
        ['/\bsocks\b/i',                             'socks'], // new leaf type — no existing type fits hosiery; self-flagged, low confidence
        ['/\bcape\b/i',                               'cape'], // new leaf type — closest existing type (jacket) would wrongly mix a costume item into real outerwear recommendations; self-flagged, low confidence
    ];

    /** Sports-department garment words that mean something different in an athletic context. */
    private const SPORTS_BOTTOM = '/leggings|shorts/i';
    private const SPORTS_TOP    = '/\btop\b|tank|tee\b|singlet|jersey|\bbra\b|\bkit\b/i';

    public function handle(): int
    {
        $total = Product::withoutGlobalScopes()->count();
        $chunk = (int) $this->option('chunk');

        $this->info("Backfilling product_type for {$total} products (chunk size: {$chunk})…");

        $bar  = $this->output->createProgressBar($total);
        $done = 0;
        $unmatched = [];

        Product::withoutGlobalScopes()
            ->with('category')
            ->chunkById($chunk, function ($products) use ($bar, &$done, &$unmatched) {
                foreach ($products as $product) {
                    $type = $this->classify($product);

                    if ($type === null) {
                        $unmatched[] = "{$product->id}: {$product->name}";
                    } else {
                        Product::withoutEvents(function () use ($product, $type) {
                            $product->forceFill(['product_type' => $type])->saveQuietly();
                        });
                    }

                    $done++;
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info("Done. {$done} products processed.");

        if (! empty($unmatched)) {
            $this->warn(count($unmatched) . ' product(s) did not match any rule:');
            foreach ($unmatched as $line) {
                $this->line("  - {$line}");
            }
        }

        return self::SUCCESS;
    }

    private function classify(Product $product): ?string
    {
        $name = $product->name;
        $isSports = $product->category?->name === 'Sports';

        if ($isSports && preg_match(self::SPORTS_BOTTOM, $name)) {
            return 'sportswear_bottom';
        }

        if ($isSports && preg_match(self::SPORTS_TOP, $name)) {
            return 'sportswear_top';
        }

        foreach (self::RULES as [$pattern, $type]) {
            if (preg_match($pattern, $name)) {
                return $type;
            }
        }

        return null;
    }
}
