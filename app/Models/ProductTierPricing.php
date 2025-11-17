<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ProductTierPricing extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function variant() {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
