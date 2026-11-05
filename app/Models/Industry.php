<?php

namespace App\Models;

use App\Models\Concerns\NormalizesSeo;
use App\Models\Concerns\Publishable;
use Database\Factories\IndustryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Industria atendida, con sus retos y los servicios/productos que aplican.
 */
#[RouteKey('slug')]
#[Fillable(['name', 'slug', 'summary', 'icon', 'image', 'challenges', 'applications', 'seo', 'is_published', 'sort_order'])]
class Industry extends Model
{
    /** @use HasFactory<IndustryFactory> */
    use HasFactory, NormalizesSeo, Publishable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'challenges' => 'array',
            'applications' => 'array',
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
}
