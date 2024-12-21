<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'title', 'subtitle', 'description', 'featured_image',
        'price', 'quantity', 'in_stock', 'discount_price', 'category_id', 'shop_id','is_active'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
    public function gallery()
{
    return $this->hasMany(ProductGallery::class);
}
}
