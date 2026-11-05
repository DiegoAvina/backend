<?php

namespace App\Models\Concerns;

/**
 * Guarda `seo` como null cuando todos sus campos están vacíos, para no
 * registrar cambios vacíos ni enviar objetos sin datos a la API.
 */
trait NormalizesSeo
{
    public static function bootNormalizesSeo(): void
    {
        static::saving(function (self $model): void {
            $seo = $model->getAttribute('seo');

            if (! is_array($seo)) {
                return;
            }

            $seo = array_filter(
                array_intersect_key($seo, array_flip(['title', 'description'])),
                fn (mixed $value): bool => is_string($value) && trim($value) !== '',
            );

            $model->setAttribute('seo', $seo === [] ? null : $seo);
        });
    }
}
