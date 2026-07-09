<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\SearchService;
use Illuminate\Console\Command;

class RebuildSearchIndex extends Command
{
    protected $signature   = 'search:rebuild-index {--chunk=100 : Products per DB batch}';
    protected $description = 'Rebuild the products.search_index column for all products';

    public function handle(SearchService $search): int
    {
        $total = Product::withoutGlobalScopes()->count();
        $chunk = (int) $this->option('chunk');

        $this->info("Rebuilding search_index for {$total} products (chunk size: {$chunk})…");

        $bar  = $this->output->createProgressBar($total);
        $done = 0;

        Product::withoutGlobalScopes()
            ->with(['brand', 'category'])
            ->chunkById($chunk, function ($products) use ($search, $bar, &$done) {
                foreach ($products as $product) {
                    // Use updateQuietly so the Observer doesn't fire and re-trigger
                    // this method in a recursive loop.
                    // forceFill bypasses $fillable; saveQuietly suppresses events so
                    // the Observer does not re-trigger this method recursively.
                    Product::withoutEvents(function () use ($product, $search) {
                        $product->forceFill(['search_index' => $search->buildSearchIndex($product)])->saveQuietly();
                    });

                    $done++;
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info("Done. {$done} products indexed.");

        return self::SUCCESS;
    }
}
