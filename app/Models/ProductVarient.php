<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ProductVarient extends Model
{
    use SoftDeletes;

    protected $guarded = [];
}
