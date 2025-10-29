<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'tags' => 'array',
        'status' => 'boolean',
        'in_stock' => 'boolean'
    ];

    public function primaryCategory() {
        return $this->hasOne(ProductCategory::class, 'product_id')->where('is_primary', 1);
    }

    public function primaryBrand() {
        return $this->hasOne(BrandProduct::class, 'product_id');
    }
}
