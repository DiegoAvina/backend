<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\Concerns\RendersContent;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
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
            'category' => new CategoryResource($this->whenLoaded('category')),
            'tagline' => $this->tagline,
            'description' => $this->description,
            'body' => $this->body,
            'body_html' => $this->markdownToHtml($this->body),
            'audience' => $this->audience,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'icon' => $this->icon,
            'image_url' => $this->imageUrl($this->image),
            'is_featured' => $this->is_featured,
            'features' => $this->features ?? [],
            'problems' => $this->problems ?? [],
            'modules' => $this->modules ?? [],
            'seo' => $this->seo,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
