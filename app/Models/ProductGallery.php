<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductGallery extends Model
{
    protected $table = 'product_gallery'; 
    protected $fillable = [
        'product_id',
        'image_url',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
