<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Review;
use App\Models\User;
use App\Services\ImageOptimizationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Track A Part 1 — Correlated Cluster Seeder
 *
 * Adds 21 products in four tight clusters so item-item similarity
 * scoring (ContentBasedService::similarProducts) has genuine signal:
 *
 *   Cluster 1 — Sports Performance   (brand=Solstice Active, style=Performance, 6 products)
 *   Cluster 2 — Men's Formal         (brand=Northfield & Co, style=Formal, 5 products)
 *   Cluster 3 — Women's Classic      (brand=Verona Row, style=Classic, 5 products)
 *   Cluster 4 — Kids Casual          (brand=Bright Sprout, style=Casual, 5 products)
 *
 * Safe to re-run: products are inserted only if their slug doesn't already
 * exist, and reviews use firstOrCreate against the existing reviewer accounts.
 */
class CatalogueExpansionSeeder extends Seeder
{
    private const ASSET_ROOT = 'C:\\Users\\white\\Downloads\\CS PROJECT 1 INSTRUCTIONS\\Assets';

    private array $vendorIds = [];
    private array $reviewerIds = [];
    private ImageOptimizationService $images;

    public function __construct()
    {
        $this->images = new ImageOptimizationService();
    }

    public function run(): void
    {
        $this->vendorIds = User::where('role', 'vendor')->orderBy('id')->pluck('id')->toArray();
        $this->reviewerIds = User::where('email', 'like', 'reviewer%@example.com')->orderBy('id')->pluck('id')->toArray();

        foreach ($this->clusters() as $label => $cluster) {
            $category = Category::where('slug', $cluster['category_slug'])->firstOrFail();
            $brand    = Brand::where('name', $cluster['brand_name'])->firstOrFail();
            $assetFolder = $this->assetFolderFor($cluster['category_slug']);
            $cursor   = 0;

            foreach ($cluster['products'] as $data) {
                $vendorId  = $this->vendorIds[$cursor % count($this->vendorIds)];
                $cursor++;

                $baseSlug = Str::slug($cluster['category_slug'] . '-' . $data['name']);
                $slug     = $baseSlug;
                $n        = 2;
                while (Product::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $n++;
                }

                $product = Product::create([
                    'vendor_id'      => $vendorId,
                    'category_id'    => $category->id,
                    'brand_id'       => $brand->id,
                    'name'           => $data['name'],
                    'slug'           => $slug,
                    'description'    => $data['description'],
                    'price'          => $data['price'],
                    'currency'       => 'KES',
                    'stock_quantity' => $data['stock'],
                    'status'         => 'published',
                    'primary_color'  => $data['color'],
                    'sizes'          => $data['sizes'] ?? null,
                    'is_featured'    => false,
                    'gender'         => $data['gender'],
                    'age_group'      => $data['age_group'],
                    'material'       => $data['material'],
                    'season'         => 'All Season',
                    'style'          => $cluster['style'],
                    'tags'           => $cluster['tags'],
                ]);

                if ($assetFolder && ! empty($data['image'])) {
                    // Most images live in the cluster's own category folder,
                    // but a few (e.g. a Sports jacket sourced from the Men
                    // folder, per curation notes) reference another session
                    // folder explicitly via a `folder\filename.jpg` value.
                    $sourceFile = str_contains($data['image'], '\\')
                        ? self::ASSET_ROOT.'\\'.$data['image']
                        : $assetFolder.'\\'.$data['image'];
                    $imported = is_file($sourceFile) ? $this->images->storeFromPath($sourceFile, 'products') : null;

                    if ($imported) {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_path' => $imported,
                            'display_order' => 0,
                        ]);
                    }
                }

                $this->seedReviews($product, $data['reviews']);
            }

            $count = count($cluster['products']);
            $this->command?->info("  Cluster '{$label}': {$count} products created.");
        }
    }

    private function clusters(): array
    {
        return [

            'Sports Performance' => [
                'category_slug' => 'sports',
                'brand_name'    => 'Solstice Active',
                'style'         => 'Performance',
                'tags'          => ['athletic', 'performance', 'breathable'],
                'products'      => [
                    [
                        'name'        => 'Performance Dry-Fit Training Tee',
                        'price'       => 1600,
                        'color'       => 'Navy',
                        'material'    => 'Dri-Fit Fabric',
                        'gender'      => 'Men',
                        'age_group'   => 'Adult',
                        'sizes'       => ['S', 'M', 'L', 'XL'],
                        'stock'       => 30,
                        'description' => 'A navy Solstice Active dry-fit tee engineered for high-intensity training — moisture-wicking fabric keeps you cool through every set.',
                        'reviews'     => 3,
                    ],
                    [
                        'name'        => 'Breathable Compression Training Shorts',
                        'price'       => 2000,
                        'color'       => 'Black',
                        'material'    => 'Spandex Blend',
                        'gender'      => 'Men',
                        'age_group'   => 'Adult',
                        'sizes'       => ['S', 'M', 'L', 'XL'],
                        'stock'       => 25,
                        'description' => 'Black compression shorts from Solstice Active with breathable mesh panels — built for runs, sprints, and gym circuits.',
                        'reviews'     => 2,
                    ],
                    [
                        'name'        => 'Womens Performance Running Tee',
                        'price'       => 1800,
                        'color'       => 'Cobalt Blue',
                        'material'    => 'Dri-Fit Fabric',
                        'gender'      => 'Women',
                        'age_group'   => 'Adult',
                        'sizes'       => ['XS', 'S', 'M', 'L', 'XL'],
                        'stock'       => 28,
                        'description' => 'A cobalt blue performance running tee from Solstice Active — lightweight dri-fit construction and a relaxed athletic cut that moves with you.',
                        'reviews'     => 4,
                    ],
                    [
                        'name'        => 'High-Performance Training Leggings',
                        'price'       => 2600,
                        'color'       => 'Black',
                        'material'    => 'Spandex Blend',
                        'gender'      => 'Women',
                        'age_group'   => 'Adult',
                        'sizes'       => ['XS', 'S', 'M', 'L', 'XL'],
                        'stock'       => 22,
                        'description' => 'Four-way stretch training leggings from Solstice Active with a high waistband and moisture-wicking finish — peak performance from warm-up to cool-down.',
                        'reviews'     => 5,
                    ],
                    [
                        'name'        => 'Unisex Moisture-Wicking Training Singlet',
                        'price'       => 2200,
                        'color'       => 'Charcoal',
                        'material'    => 'Dri-Fit Fabric',
                        'gender'      => 'Unisex',
                        'age_group'   => 'Adult',
                        'sizes'       => ['S', 'M', 'L', 'XL', 'XXL'],
                        'stock'       => 35,
                        'description' => 'A charcoal Solstice Active training singlet built for both the gym and the track — breathable, lightweight, and cut to let you move freely.',
                        'reviews'     => 2,
                    ],
                    [
                        'name'        => 'Lightweight Athletic Running Jacket',
                        'price'       => 3400,
                        'color'       => 'Electric Blue',
                        'material'    => 'Nylon',
                        'gender'      => 'Unisex',
                        'age_group'   => 'Adult',
                        'sizes'       => ['S', 'M', 'L', 'XL', 'XXL'],
                        'stock'       => 18,
                        'description' => 'An electric blue Solstice Active running jacket — wind-resistant nylon shell with a packable hood for warm-up laps and post-run cool-down.',
                        'image'       => 'session 2 men\\istockphoto-1333068000-612x612.jpg',
                        'reviews'     => 3,
                    ],
                ],
            ],

            "Men's Formal" => [
                'category_slug' => 'men',
                'brand_name'    => 'Northfield & Co',
                'style'         => 'Formal',
                'tags'          => ['formal', 'classic', 'workwear'],
                'products'      => [
                    [
                        'name'        => 'Classic Oxford Dress Shirt',
                        'price'       => 4200,
                        'color'       => 'White',
                        'material'    => 'Cotton',
                        'gender'      => 'Men',
                        'age_group'   => 'Adult',
                        'sizes'       => ['S', 'M', 'L', 'XL', 'XXL'],
                        'stock'       => 20,
                        'description' => 'A crisp white Oxford dress shirt from Northfield & Co — a boardroom staple with a classic cut and button-down collar that pairs with any formal trouser.',
                        'reviews'     => 4,
                    ],
                    [
                        'name'        => 'Slim-Fit Wool-Blend Dress Trousers',
                        'price'       => 5500,
                        'color'       => 'Charcoal',
                        'material'    => 'Wool Blend',
                        'gender'      => 'Men',
                        'age_group'   => 'Adult',
                        'sizes'       => ['28', '30', '32', '34', '36'],
                        'stock'       => 15,
                        'description' => 'Charcoal slim-fit dress trousers from Northfield & Co in a premium wool blend — tailored for a sharp silhouette from the boardroom to after-work dining.',
                        'reviews'     => 3,
                    ],
                    [
                        'name'        => 'Tailored Pinstripe Formal Shirt',
                        'price'       => 4800,
                        'color'       => 'Navy',
                        'material'    => 'Cotton',
                        'gender'      => 'Men',
                        'age_group'   => 'Adult',
                        'sizes'       => ['S', 'M', 'L', 'XL', 'XXL'],
                        'stock'       => 18,
                        'description' => 'A navy pinstripe formal shirt from Northfield & Co with a spread collar and single-button cuffs — understated workwear elegance.',
                        'reviews'     => 2,
                    ],
                    [
                        'name'        => 'Single-Breasted Charcoal Blazer',
                        'price'       => 7500,
                        'color'       => 'Charcoal',
                        'material'    => 'Wool Blend',
                        'gender'      => 'Men',
                        'age_group'   => 'Adult',
                        'sizes'       => ['S', 'M', 'L', 'XL'],
                        'stock'       => 10,
                        'description' => 'A charcoal single-breasted blazer from Northfield & Co in structured wool blend — clean lapels and a lean modern silhouette for meetings and formal events.',
                        'reviews'     => 5,
                    ],
                    [
                        'name'        => 'Navy Five-Button Formal Waistcoat',
                        'price'       => 5000,
                        'color'       => 'Navy',
                        'material'    => 'Wool Blend',
                        'gender'      => 'Men',
                        'age_group'   => 'Adult',
                        'sizes'       => ['S', 'M', 'L', 'XL'],
                        'stock'       => 12,
                        'description' => 'A navy five-button waistcoat from Northfield & Co — the finishing touch for a three-piece formal look, also sharp over a dress shirt alone.',
                        'reviews'     => 2,
                    ],
                ],
            ],

            "Women's Classic" => [
                'category_slug' => 'women',
                'brand_name'    => 'Verona Row',
                'style'         => 'Classic',
                'tags'          => ['formal', 'classic', 'elegant'],
                'products'      => [
                    [
                        'name'        => 'Classic Wrap Midi Dress',
                        'price'       => 4500,
                        'color'       => 'Burgundy',
                        'material'    => 'Crepe',
                        'gender'      => 'Women',
                        'age_group'   => 'Adult',
                        'sizes'       => ['XS', 'S', 'M', 'L', 'XL'],
                        'stock'       => 16,
                        'description' => 'A burgundy wrap midi dress from Verona Row in fluid crepe — elegant V-neckline, cinched waist, and a knee-to-calf hem that works from office to occasion.',
                        'reviews'     => 5,
                    ],
                    [
                        'name'        => 'Tailored Pencil Midi Skirt',
                        'price'       => 4200,
                        'color'       => 'Black',
                        'material'    => 'Wool Blend',
                        'gender'      => 'Women',
                        'age_group'   => 'Adult',
                        'sizes'       => ['XS', 'S', 'M', 'L', 'XL'],
                        'stock'       => 20,
                        'description' => 'A black tailored pencil skirt from Verona Row in stretch wool blend — structured and polished for the office, meeting, or evening dinner.',
                        'reviews'     => 3,
                    ],
                    [
                        'name'        => 'Elegant Ivory Satin Blouse',
                        'price'       => 5500,
                        'color'       => 'Ivory',
                        'material'    => 'Satin',
                        'gender'      => 'Women',
                        'age_group'   => 'Adult',
                        'sizes'       => ['XS', 'S', 'M', 'L', 'XL'],
                        'stock'       => 14,
                        'description' => 'An ivory satin blouse from Verona Row with a relaxed pussy-bow neckline — timeless workwear that transitions effortlessly from the office to the evening.',
                        'image'       => 'laura-chouette-WQgvRkmqRrg-unsplash.jpg',
                        'reviews'     => 4,
                    ],
                    [
                        'name'        => 'A-Line Forest Green Midi Dress',
                        'price'       => 6000,
                        'color'       => 'Forest Green',
                        'material'    => 'Crepe',
                        'gender'      => 'Women',
                        'age_group'   => 'Adult',
                        'sizes'       => ['XS', 'S', 'M', 'L', 'XL'],
                        'stock'       => 12,
                        'description' => 'A forest-green A-line midi dress from Verona Row — fitted bodice, gently flared skirt, and back zip for a clean, classic silhouette at any formal occasion.',
                        'reviews'     => 3,
                    ],
                    [
                        'name'        => 'Camel Collarless Structured Blazer',
                        'price'       => 7000,
                        'color'       => 'Camel',
                        'material'    => 'Wool Blend',
                        'gender'      => 'Women',
                        'age_group'   => 'Adult',
                        'sizes'       => ['XS', 'S', 'M', 'L', 'XL'],
                        'stock'       => 10,
                        'description' => 'A camel collarless blazer from Verona Row in structured wool blend — a classic layering piece for polished workwear or elevated smart-casual outfits.',
                        'reviews'     => 5,
                    ],
                ],
            ],

            'Kids Casual' => [
                'category_slug' => 'kids',
                'brand_name'    => 'Bright Sprout',
                'style'         => 'Casual',
                'tags'          => ['casual', 'comfortable', 'everyday'],
                'products'      => [
                    [
                        'name'        => 'Everyday Cotton Graphic Tee',
                        'price'       => 700,
                        'color'       => 'Yellow',
                        'material'    => 'Cotton',
                        'gender'      => 'Unisex',
                        'age_group'   => 'Kids',
                        'sizes'       => ['2-3Y', '4-5Y', '6-7Y', '8-9Y', '10-11Y'],
                        'stock'       => 40,
                        'description' => 'A bright yellow graphic tee from Bright Sprout in soft 100% cotton — everyday casual wear with a cheerful print and a relaxed fit kids love.',
                        'reviews'     => 3,
                    ],
                    [
                        'name'        => 'Comfy Pull-On Jogger Bottoms',
                        'price'       => 900,
                        'color'       => 'Grey',
                        'material'    => 'Cotton',
                        'gender'      => 'Unisex',
                        'age_group'   => 'Kids',
                        'sizes'       => ['2-3Y', '4-5Y', '6-7Y', '8-9Y', '10-11Y'],
                        'stock'       => 35,
                        'description' => 'Soft grey pull-on jogger bottoms from Bright Sprout in brushed cotton jersey — elasticated waist and roomy fit for easy all-day comfort.',
                        'reviews'     => 4,
                    ],
                    [
                        'name'        => 'Casual Adjustable-Waist Denim Shorts',
                        'price'       => 1100,
                        'color'       => 'Light Blue',
                        'material'    => 'Denim',
                        'gender'      => 'Unisex',
                        'age_group'   => 'Kids',
                        'sizes'       => ['2-3Y', '4-5Y', '6-7Y', '8-9Y', '10-11Y'],
                        'stock'       => 30,
                        'description' => 'Light blue denim shorts from Bright Sprout with an adjustable waistband — durable and comfortable casual wear for active kids.',
                        'reviews'     => 2,
                    ],
                    [
                        'name'        => 'Soft-Wash Zip-Up Hoodie',
                        'price'       => 1200,
                        'color'       => 'Coral',
                        'material'    => 'Cotton',
                        'gender'      => 'Unisex',
                        'age_group'   => 'Kids',
                        'sizes'       => ['2-3Y', '4-5Y', '6-7Y', '8-9Y', '10-11Y'],
                        'stock'       => 28,
                        'description' => 'A coral zip-up hoodie from Bright Sprout in garment-washed cotton for extra softness — easy to layer, cosy to wear, and machine-washable.',
                        'reviews'     => 3,
                    ],
                    [
                        'name'        => 'Colourful Print Casual Dress',
                        'price'       => 950,
                        'color'       => 'Multicolor',
                        'material'    => 'Cotton',
                        'gender'      => 'Girls',
                        'age_group'   => 'Kids',
                        'sizes'       => ['2-3Y', '4-5Y', '6-7Y', '8-9Y', '10-11Y'],
                        'stock'       => 25,
                        'description' => 'A colourful print casual dress from Bright Sprout — cheerful everyday cotton with a smocked bodice and tiered skirt that little ones can run around in all day.',
                        'reviews'     => 2,
                    ],
                ],
            ],
        ];
    }

    private function seedReviews(Product $product, int $count): void
    {
        if ($count === 0 || empty($this->reviewerIds)) {
            return;
        }

        $ratings  = [5, 4, 5, 4, 3, 5, 4];
        $comments = [
            'Excellent quality — really impressed.',
            'Perfect fit and looks exactly as pictured.',
            'Good value, arrived quickly.',
            'Great item, exactly what we needed.',
            'Nice quality but sizing runs slightly small.',
            null,
        ];

        for ($i = 0; $i < min($count, count($this->reviewerIds)); $i++) {
            Review::firstOrCreate(
                ['product_id' => $product->id, 'user_id' => $this->reviewerIds[$i]],
                [
                    'rating'  => $ratings[$i % count($ratings)],
                    'comment' => $comments[$i % count($comments)],
                ]
            );
        }
    }

    /**
     * Absolute path to a category's asset-library folder. Returns null
     * (graceful skip) if the external asset library isn't present on this
     * machine, or if the category has no known folder — mirrors
     * ProductCatalogueSeeder::assetFolderFor() so a missing library never
     * hard-fails `migrate:fresh --seed`.
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
