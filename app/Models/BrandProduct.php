<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class BrandProduct extends Model
{
    use SoftDeletes;

    protected $table = 'brand_product';

    protected $guarded = [];

    public function product() {
        return $this->belongsTo(Product::class, 'brand_id');
    }
}
