<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function warehouse() {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');        
    }
}
