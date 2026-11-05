<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Models\Concerns\Publishable;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Producto digital propio. `status` comunica su estado comercial real.
 */
#[RouteKey('slug')]
#[Fillable([
    'category_id', 'name', 'slug', 'tagline', 'description', 'audience', 'status', 'icon',
    'features', 'problems', 'modules', 'seo', 'is_featured', 'is_published', 'sort_order',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, Publishable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'features' => 'array',
            'problems' => 'array',
            'modules' => 'array',
            'seo' => 'array',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsToMany<Industry, $this>
     */
    public function industries(): BelongsToMany
    {
        return $this->belongsToMany(Industry::class)->withPivot('sort_order');
    }

    /**
     * @return BelongsToMany<Solution, $this>
     */
    public function solutions(): BelongsToMany
    {
        return $this->belongsToMany(Solution::class)->withPivot('sort_order');
    }

    /**
     * @return HasMany<DemoRequest, $this>
     */
    public function demoRequests(): HasMany
    {
        return $this->hasMany(DemoRequest::class);
    }
}
