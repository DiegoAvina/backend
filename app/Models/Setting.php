<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Valor de configuración del sitio. El catálogo de claves permitidas, su
 * tipo y si son públicas vive en `App\Enums\SettingKey`.
 */
#[Fillable(['key', 'group', 'type', 'value', 'is_public', 'sort_order'])]
class Setting extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function public(Builder $query): void
    {
        $query->where('is_public', true);
    }
}
