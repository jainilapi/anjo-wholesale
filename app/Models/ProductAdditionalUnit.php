<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ProductAdditionalUnit extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function parent()
    {
        return $this->belongsTo(ProductAdditionalUnit::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProductAdditionalUnit::class, 'parent_id');
    }
}
