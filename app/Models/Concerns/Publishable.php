<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Contenido que se publica con un interruptor (`is_published`) y se ordena
 * manualmente (`sort_order`). Solo lo publicado llega a la API pública.
 */
trait Publishable
{
    /**
     * Initialize the trait: casts comunes del contenido publicable.
     */
    public function initializePublishable(): void
    {
        $this->mergeCasts([
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ]);
    }

    /**
     * Limita la consulta al contenido publicado.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where($this->qualifyColumn('is_published'), true);
    }

    /**
     * Orden editorial por defecto: `sort_order` y después nombre.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy($this->qualifyColumn('sort_order'))->orderBy($this->qualifyColumn('name'));
    }
}
