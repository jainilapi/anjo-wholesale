<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function productBaseUnits()
    {
        return $this->hasMany(ProductBaseUnit::class);
    }

    public function productAdditionalUnits()
    {
        return $this->hasMany(ProductAdditionalUnit::class);
    }
}
