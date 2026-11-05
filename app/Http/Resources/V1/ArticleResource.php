<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\Concerns\RendersContent;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * El cuerpo del artículo solo se incluye en el detalle, no en el listado.
 *
 * @mixin Article
 */
class ArticleResource extends JsonResource
{
    use RendersContent;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'body' => $this->when($request->routeIs('api.v1.articles.show'), $this->body),
            'body_html' => $this->when($request->routeIs('api.v1.articles.show'), fn () => $this->markdownToHtml($this->body)),
            'cover_image_url' => $this->imageUrl($this->cover_image),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'author' => $this->whenLoaded('author', fn () => $this->author?->name),
            'published_at' => $this->published_at?->toIso8601String(),
            'seo' => $this->seo,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
