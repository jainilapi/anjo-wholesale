<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ProductSubtitue extends Model
{
    use SoftDeletes;

    protected $guarded = [];


    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function substituteProduct()
    {
        return $this->belongsTo(Product::class, 'source_product_id');
    }

    public function substituteVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'source_variant_id');
    }    
}
