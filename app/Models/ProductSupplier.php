<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ProductSupplier extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function variant()
    {
        return $this->belongsTo(ProductVarient::class, 'product_varient_id');
    }

    public function supplier()
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }
}
