<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'vendor_id',
        'category_id',
        'brand_id',
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'stock_quantity',
        'status',
        'primary_color',
        'sizes',
        'is_featured',
        'gender',
        'age_group',
        'material',
        'season',
        'style',
        'product_type',
        'tags',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sizes' => 'array',
            'is_featured' => 'boolean',
            'tags' => 'array',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function collectionItems(): HasMany
    {
        return $this->hasMany(CollectionItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(UserInteraction::class);
    }

    public function recommendationLogs(): HasMany
    {
        return $this->hasMany(RecommendationLog::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', 'published');
    }

    /**
     * A product's gender is compatible with $gender if they match exactly,
     * or either one is "Unisex" (Men↔Men/Unisex, Women↔Women/Unisex,
     * Boys↔Boys/Unisex, Girls↔Girls/Unisex, Unisex↔anything).
     *
     * A null/Unisex $gender applies no constraint at all — an anchor with
     * no gender or a Unisex anchor is compatible with every candidate,
     * which also covers the "Unisex↔anything" case from the anchor side.
     */
    public function scopeGenderCompatible(Builder $query, ?string $gender): Builder
    {
        if ($gender === null || $gender === 'Unisex') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($gender) {
            $q->where('gender', $gender)->orWhere('gender', 'Unisex');
        });
    }

    /**
     * A product's age_group is compatible with $ageGroup only if they match
     * exactly. Only two values exist in this catalogue — "Adult" and "Kids"
     * (confirmed against the live data; "Boys"/"Girls" are gender values
     * under age_group="Kids", not separate age_group values) — and neither
     * has a "matches anything" value the way gender's "Unisex" does. That is
     * deliberate: a Unisex-*gender* Kids shoe must still only match other
     * Kids-*age_group* items, never adult Unisex items. Gender and age_group
     * are independent dimensions; a free pass on one must never imply a free
     * pass on the other.
     */
    public function scopeAgeGroupCompatible(Builder $query, ?string $ageGroup): Builder
    {
        if ($ageGroup === null) {
            return $query;
        }

        return $query->where('age_group', $ageGroup);
    }

    /**
     * Groups product_type values that are close substitutes for one another —
     * used as a tier-2 fallback whenever an exact-type candidate pool is too
     * thin (e.g. belt=1, waistcoat=1 in the current catalogue). Each list
     * includes its own members' exact type, so querying against a sibling
     * list also naturally covers the tier-1 exact-match candidates.
     *
     * Lives on the model (not on any one consuming service) because both
     * ContentBasedService::similarProducts() and
     * RecommendationRepository::coViewedProductIds() need the same mapping.
     */
    public const TYPE_SUPERTYPES = [
        'footwear'         => ['shoes', 'sneakers', 'sandals', 'heels', 'boots'],
        'outerwear'        => ['jacket', 'blazer', 'hoodie'],
        'bottoms'          => ['trousers', 'jeans', 'joggers', 'shorts', 'skirt'],
        'tops'             => ['shirt', 'tee', 'blouse', 'waistcoat'],
        'activewear'       => ['sportswear_top', 'sportswear_bottom'],
        'jewelry'          => ['necklace', 'earrings', 'bracelet', 'watch'],
        'eyewear_headwear' => ['sunglasses', 'hat'],
        'leathergoods'     => ['wallet', 'belt', 'bag'],
    ];

    /**
     * The full sibling list (including $productType itself) for whichever
     * supertype $productType belongs to, or [] if it belongs to none
     * (e.g. "dress", "romper" — no natural partner exists in this taxonomy).
     */
    public static function supertypeSiblingTypes(?string $productType): array
    {
        if ($productType === null) {
            return [];
        }

        foreach (self::TYPE_SUPERTYPES as $members) {
            if (in_array($productType, $members, true)) {
                return $members;
            }
        }

        return [];
    }
}
