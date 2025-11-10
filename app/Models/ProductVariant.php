<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $guarded = [];

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
}
