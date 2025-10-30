<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'tags' => 'array',
        'status' => 'boolean'
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public static function buildCategoryTree($parentId = null) {
        $categories = Category::where('parent_id', $parentId)
            ->whereNull('deleted_at')
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        foreach ($categories as $category) {
            $category->children = self::buildCategoryTree($category->id);
        }

        return $categories;
    }
}
