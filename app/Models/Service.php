<?php

namespace App\Models;

use App\Enums\ServiceGroup;
use App\Models\Concerns\Publishable;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Servicio de la unidad IT Solutions, agrupado por línea (`group`).
 */
#[RouteKey('slug')]
#[Fillable([
    'name', 'slug', 'group', 'summary', 'headline', 'description', 'icon',
    'benefits', 'includes', 'ideal_for', 'seo', 'is_published', 'sort_order',
])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, Publishable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'group' => ServiceGroup::class,
            'benefits' => 'array',
            'includes' => 'array',
            'ideal_for' => 'array',
            'seo' => 'array',
        ];
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
     * @return HasMany<QuoteRequest, $this>
     */
    public function quoteRequests(): HasMany
    {
        return $this->hasMany(QuoteRequest::class);
    }
}
