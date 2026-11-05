<?php

namespace App\Http\Resources\V1;

use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Referencia breve a un servicio o producto relacionado (slug + nombre),
 * para enlazar sin repetir el contenido completo.
 *
 * @mixin Service|Product
 */
class RelatedContentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
        ];
    }
}
