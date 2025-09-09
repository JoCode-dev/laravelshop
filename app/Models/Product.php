<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperProduct
 */
class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'image',
        'stock',
    ];

    public function count(): int
    {
        return $this->count();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
