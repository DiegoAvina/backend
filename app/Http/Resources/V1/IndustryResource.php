<?php

namespace App\Http\Resources\V1;

use App\Models\Industry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Industry
 */
class IndustryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'summary' => $this->summary,
            'icon' => $this->icon,
            'challenges' => $this->challenges ?? [],
            'applications' => $this->applications ?? [],
            'related_services' => RelatedContentResource::collection($this->whenLoaded('services')),
            'related_products' => RelatedContentResource::collection($this->whenLoaded('products')),
            'seo' => $this->seo,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
