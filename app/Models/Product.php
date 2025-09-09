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

    // Relation avec les lignes de commande (order_items)
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
