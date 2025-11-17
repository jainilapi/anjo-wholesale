<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function category() {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
