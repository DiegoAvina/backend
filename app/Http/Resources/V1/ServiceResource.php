<?php

namespace App\Http\Resources\V1;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Service
 */
class ServiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'group' => $this->group->value,
            'group_label' => $this->group->label(),
            'summary' => $this->summary,
            'headline' => $this->headline,
            'description' => $this->description,
            'icon' => $this->icon,
            'benefits' => $this->benefits ?? [],
            'includes' => $this->includes ?? [],
            'ideal_for' => $this->ideal_for ?? [],
            'seo' => $this->seo,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
