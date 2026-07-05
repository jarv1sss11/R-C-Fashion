<?php

namespace App\Console\Commands;

use App\Services\ImageOptimizationService;
use Illuminate\Console\Command;

/**
 * Phase 13.1 Step 10: copies the curated editorial photos (hero, login/
 * register, lifestyle, category banners) from the external asset library
 * into public/images/editorial/, resizing/re-encoding via GD on the way in.
 * The destination filenames match what editorial-card.blade.php,
 * auth-showcase.blade.php, category-banner-grid.blade.php, and
 * lifestyle-banner.blade.php already reference — those components render a
 * CSS-gradient fallback via file_exists() until this command has been run.
 */
class ImportEditorialImages extends Command
{
    protected $signature = 'editorial:import';

    protected $description = 'Copy and GD-optimize the curated editorial photos into public/images/editorial/';

    private const ASSET_ROOT = 'C:\\Users\\white\\Downloads\\CS PROJECT 1 INSTRUCTIONS\\Assets\\session 1 editorial';

    /** @var array<string,string> destination relative path => source relative path (within ASSET_ROOT) */
    private const MAP = [
        'hero/hero-1.jpg' => '01_Hero\\pexels-shvets-production-9775439.jpg',
        'hero/hero-2.jpg' => '01_Hero\\pexels-victoria-kibaki-1609529710-27594071.jpg',
        'hero/hero-3.jpg' => '01_Hero\\pexels-noel-puebla-280715950-13450705.jpg',

        'auth/login-register-1.jpg' => '02_Login_Register\\pexels-vlada-karpovich-4452374.jpg',
        'auth/login-register-2.jpg' => '02_Login_Register\\pexels-rachel-claire-4992652.jpg',
        'auth/login-register-3.jpg' => '02_Login_Register\\pexels-taryn-elliott-5405630.jpg',

        'lifestyle/home.jpg' => '03_Lifestyle\\pexels-godisable-jacob-226636-1068642.jpg',

        'categories/men.jpg' => '04_Category_Banners\\pexels-h-512756904-38171767.jpg',
        'categories/women.jpg' => '04_Category_Banners\\pexels-kathleen-e-691078112-33629309.jpg',
        'categories/accessories.jpg' => '04_Category_Banners\\pexels-taryn-elliott-5405645.jpg',
    ];

    /**
     * Kids/Sports have no dedicated category-banner photography in the
     * asset library (it predates those categories) — reusing an
     * already-vetted product photo from each category's own folder instead
     * of introducing an unvetted image.
     */
    private const CATEGORY_FALLBACKS = [
        'categories/kids.jpg' => 'Session 4 Kids\\vivek-wtTu4Rd9OX8-unsplash.jpg',
        'categories/sports.jpg' => 'Session 5 Sports\\andre-hunter-RPKdvPcYAUo-unsplash.jpg',
    ];

    public function handle(ImageOptimizationService $images): int
    {
        $assetsParent = dirname(self::ASSET_ROOT);
        $ok = 0;

        foreach (self::MAP as $destination => $source) {
            $sourcePath = self::ASSET_ROOT.'\\'.$source;
            $ok += (int) $this->copyOne($images, $sourcePath, $destination);
        }

        foreach (self::CATEGORY_FALLBACKS as $destination => $source) {
            $sourcePath = $assetsParent.'\\'.$source;
            $ok += (int) $this->copyOne($images, $sourcePath, $destination);
        }

        $this->info("Editorial images imported: {$ok}.");

        return self::SUCCESS;
    }

    private function copyOne(ImageOptimizationService $images, string $sourcePath, string $destination): bool
    {
        $destinationPath = public_path('images/editorial/'.$destination);

        if (! is_file($sourcePath)) {
            $this->warn("Source not found, skipped: {$sourcePath}");

            return false;
        }

        $result = $images->copyToPublicPath($sourcePath, $destinationPath);

        if ($result) {
            $this->line("Imported {$destination}");
        } else {
            $this->warn("Failed to process: {$sourcePath}");
        }

        return $result;
    }
}
