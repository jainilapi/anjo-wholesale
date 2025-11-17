<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function product() {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function baseUnit() {
        return $this->hasOne(ProductBaseUnit::class, 'variant_id');
    }

    public function additionalUnits() {
        return $this->hasMany(ProductAdditionalUnit::class, 'variant_id');
    }

    public function inventories() {
        return $this->hasMany(Inventory::class, 'product_variant_id');
    }

    public function suppliers() {
        return $this->hasMany(ProductSupplier::class, 'product_variant_id');
    }

    public function substitutes()
    {
        return $this->hasMany(ProductSubtitue::class, 'variant_id');
    }

    public function attributes()
    {
        return $this->hasMany(ProductAttributeVariant::class, 'variant_id');
    }

    public function variantImage()
    {
        return $this->hasOne(ProductVariantImage::class, 'variant_id')
                   ->where('is_primary', 1);
    }
}
