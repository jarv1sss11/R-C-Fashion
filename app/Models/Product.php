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
}
