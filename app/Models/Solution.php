<?php

namespace App\Models;

use App\Models\Concerns\Publishable;
use Database\Factories\SolutionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Solución de negocio: un problema empresarial y cómo se aborda.
 */
#[RouteKey('slug')]
#[Fillable([
    'name', 'slug', 'headline', 'summary', 'icon', 'signals', 'approach', 'outcomes',
    'seo', 'is_published', 'sort_order',
])]
class Solution extends Model
{
    /** @use HasFactory<SolutionFactory> */
    use HasFactory, Publishable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'signals' => 'array',
            'approach' => 'array',
            'outcomes' => 'array',
            'seo' => 'array',
        ];
    }

    /**
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    /**
     * @return HasMany<DiagnosticRequest, $this>
     */
    public function diagnosticRequests(): HasMany
    {
        return $this->hasMany(DiagnosticRequest::class);
    }
}
