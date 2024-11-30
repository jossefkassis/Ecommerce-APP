<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    protected $fillable = ['name', 'is_active'];

    // public function products(): HasMany
    // {
    //     return $this->hasMany(Product::class);
    // }
}
