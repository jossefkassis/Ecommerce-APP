<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $table = 'order_items'; // Ensure this matches your database table name

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
        'title',
        'image_url',
    ];
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
