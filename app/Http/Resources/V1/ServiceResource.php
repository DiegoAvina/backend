<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\Concerns\RendersContent;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Service
 */
class ServiceResource extends JsonResource
{
    use RendersContent;

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
            'body' => $this->body,
            'body_html' => $this->markdownToHtml($this->body),
            'icon' => $this->icon,
            'image_url' => $this->imageUrl($this->image),
            'benefits' => $this->benefits ?? [],
            'includes' => $this->includes ?? [],
            'ideal_for' => $this->ideal_for ?? [],
            'seo' => $this->seo,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
