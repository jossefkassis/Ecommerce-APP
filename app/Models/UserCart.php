<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserCart extends Model
{
    protected $fillable = ['user_id'];

    public function items(): HasMany
{
    return $this->hasMany(CartItem::class, 'cart_id');
}
}
