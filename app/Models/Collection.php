<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collection extends Model
{
    protected $fillable = [
        'title',
        'description',
        'curated_by',
        'type',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CollectionItem::class);
    }
}
