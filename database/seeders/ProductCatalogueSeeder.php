<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Review;
use App\Models\User;
use App\Models\VendorProfile;
use App\Services\ImageOptimizationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Phase 13.1 catalogue expansion, rebuilt image-led in the Phase 13.1
 * Completion Pass: each per-category data file in database/seeders/data/
 * now carries an explicit `image` key (a literal filename in the category's
 * asset-library folder) chosen AFTER visually verifying the photo, rather
 * than a folder glob paired mechanically by array index. This seeder
 * resolves brand/category/vendor foreign keys, imports that exact photo per
 * product (GD-resized via ImageOptimizationService), and seeds a handful of
 * reviews so ratings/availability filters have real data to work with.
 *
 * The asset library lives outside the repo (a local folder of licensed
 * stock photography, not committed to git) — if it isn't present on a given
 * machine, or a product's named file is missing, image import is skipped
 * gracefully and the product is still created without a photo, so
 * `migrate:fresh --seed` never hard-fails here.
 */
class ProductCatalogueSeeder extends Seeder
{
    private const ASSET_ROOT = 'C:\\Users\\white\\Downloads\\CS PROJECT 1 INSTRUCTIONS\\Assets';

    private ImageOptimizationService $images;

    public function __construct()
    {
        $this->images = new ImageOptimizationService();
    }

    public function run(): void
    {
        // Some source photos in the asset library are very large (7000px+
        // wide); GD decodes them to an uncompressed bitmap in memory before
        // resizing, which can exceed the default CLI memory_limit across a
        // 320-product import. Raised only for this seeder's process.
        ini_set('memory_limit', '2048M');

        $vendors = $this->vendors();
        $reviewers = $this->reviewers();

        $categoryDefinitions = [
            'men' => 'MenProducts.php',
            'women' => 'WomenProducts.php',
            'kids' => 'KidsProducts.php',
            'sports' => 'SportsProducts.php',
            'accessories' => 'AccessoriesProducts.php',
        ];

        $totalProducts = 0;
        $totalImages = 0;

        foreach ($categoryDefinitions as $slug => $file) {
            $category = Category::where('slug', $slug)->firstOrFail();
            $products = require __DIR__.'/data/'.$file;
            $assetFolder = $this->assetFolderFor($slug);
            $vendorCursor = 0;

            foreach ($products as $data) {
                $brand = Brand::where('name', $data['brand'])->first();
                $vendor = $vendors[$vendorCursor % count($vendors)];
                $vendorCursor++;

                $slugCandidate = Str::slug($slug.'-'.$data['name']);
                $uniqueSlug = $slugCandidate;
                $suffix = 1;
                while (Product::where('slug', $uniqueSlug)->exists()) {
                    $uniqueSlug = $slugCandidate.'-'.(++$suffix);
                }

                $product = Product::create([
                    'vendor_id' => $vendor->id,
                    'category_id' => $category->id,
                    'brand_id' => $brand?->id,
                    'name' => $data['name'],
                    'slug' => $uniqueSlug,
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'currency' => 'KES',
                    'stock_quantity' => $data['stock'],
                    'status' => 'published',
                    'primary_color' => $data['color'],
                    'sizes' => $data['sizes'],
                    'is_featured' => $data['is_featured'],
                    'gender' => $data['gender'],
                    'age_group' => $data['age_group'],
                    'material' => $data['material'],
                    'season' => $data['season'],
                    'style' => $data['style'],
                    'tags' => $data['tags'],
                ]);

                $totalProducts++;

                if ($assetFolder && ! empty($data['image'])) {
                    $sourceFile = $assetFolder.'\\'.$data['image'];
                    $imported = is_file($sourceFile) ? $this->images->storeFromPath($sourceFile, 'products') : null;

                    if ($imported) {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_path' => $imported,
                            'display_order' => 0,
                        ]);
                        $totalImages++;
                    }
                }

                $this->seedReviews($product, $reviewers, $data['review_count']);
            }

            $this->command?->info("Seeded {$slug}: ".count($products).' products.');
        }

        $this->command?->info("Catalogue expansion complete: {$totalProducts} products, {$totalImages} images.");
    }

    /**
     * A handful of vendor accounts (in addition to the Step-1 demo vendor)
     * so the ~320-product catalogue reads like a real multi-seller
     * marketplace rather than one storefront.
     */
    private function vendors(): array
    {
        $definitions = [
            ['name' => 'Carol Wanjiru', 'email' => 'carol@example.com', 'store' => "Carol's Closet", 'slug' => 'carols-closet'],
            ['name' => 'Daniel Otieno', 'email' => 'daniel.vendor@example.com', 'store' => 'Northgate Traders', 'slug' => 'northgate-traders'],
            ['name' => 'Faith Mwangi', 'email' => 'faith.vendor@example.com', 'store' => 'UrbanThread Ltd', 'slug' => 'urbanthread-ltd'],
            ['name' => 'Peter Kamau', 'email' => 'peter.vendor@example.com', 'store' => 'Meridian Retail', 'slug' => 'meridian-retail'],
            ['name' => 'Grace Achieng', 'email' => 'grace.vendor@example.com', 'store' => 'Highline Apparel', 'slug' => 'highline-apparel'],
        ];

        $vendors = [];

        foreach ($definitions as $def) {
            $user = User::firstOrCreate(
                ['email' => $def['email']],
                [
                    'name' => $def['name'],
                    'password' => Hash::make('password123'),
                    'role' => 'vendor',
                    'status' => 'active',
                ]
            );

            VendorProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'store_name' => $def['store'],
                    'store_slug' => $def['slug'],
                    'description' => "Quality fashion from {$def['store']} — trusted on R&C Fashion.",
                    'approval_status' => 'approved',
                    'phone' => '07'.random_int(10000000, 99999999),
                    'email' => $def['email'],
                    'county' => 'Nairobi',
                ]
            );

            $vendors[] = $user;
        }

        return $vendors;
    }

    /**
     * Demo buyer accounts used purely as review authors — enough of them
     * that the `reviews` table's (product_id, user_id) unique constraint
     * never blocks the review counts the data files ask for (max 15).
     */
    private function reviewers(): array
    {
        $reviewers = [];

        for ($i = 1; $i <= 15; $i++) {
            $reviewers[] = User::firstOrCreate(
                ['email' => "reviewer{$i}@example.com"],
                [
                    'name' => "Demo Reviewer {$i}",
                    'password' => Hash::make('password123'),
                    'role' => 'buyer',
                    'status' => 'active',
                ]
            );
        }

        return $reviewers;
    }

    private function seedReviews(Product $product, array $reviewers, int $count): void
    {
        $ratingCycle = [5, 4, 5, 3, 4, 5, 4, 2, 5, 4, 3, 5, 4, 5, 4];
        $comments = [
            'Great fit and the fabric feels premium.',
            'Exactly as pictured, fast delivery too.',
            'Good value for the price, would buy again.',
            'Nice quality but runs slightly small.',
            'Colour is even better in person.',
            null,
            null,
        ];

        for ($i = 0; $i < $count; $i++) {
            Review::firstOrCreate(
                ['product_id' => $product->id, 'user_id' => $reviewers[$i % count($reviewers)]->id],
                [
                    'rating' => $ratingCycle[$i % count($ratingCycle)],
                    'comment' => $comments[$i % count($comments)],
                ]
            );
        }
    }

    /**
     * Absolute path to a category's asset-library folder, used to resolve
     * each product's `image` filename to a real file on disk. Returns null
     * (graceful skip) if the external asset library isn't present on this
     * machine, or if the category has no known folder.
     */
    private function assetFolderFor(string $categorySlug): ?string
    {
        $folders = [
            'men' => 'session 2 men',
            'women' => 'Session 3 Women',
            'kids' => 'Session 4 Kids',
            'sports' => 'Session 5 Sports',
            'accessories' => 'Session 6 Accessories',
        ];

        $folder = $folders[$categorySlug] ?? null;

        if (! $folder) {
            return null;
        }

        $dir = self::ASSET_ROOT.'\\'.$folder;

        return is_dir($dir) ? $dir : null;
    }
}
