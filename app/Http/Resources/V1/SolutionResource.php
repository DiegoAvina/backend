<?php

namespace App\Http\Resources\V1;

use App\Models\Solution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Solution
 */
class SolutionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'headline' => $this->headline,
            'summary' => $this->summary,
            'icon' => $this->icon,
            'signals' => $this->signals ?? [],
            'approach' => $this->approach ?? [],
            'outcomes' => $this->outcomes ?? [],
            'related_services' => RelatedContentResource::collection($this->whenLoaded('services')),
            'related_products' => RelatedContentResource::collection($this->whenLoaded('products')),
            'seo' => $this->seo,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
