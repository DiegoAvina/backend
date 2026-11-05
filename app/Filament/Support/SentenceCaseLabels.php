<?php

namespace App\Filament\Support;

use Illuminate\Support\Str;

/**
 * Etiquetas en mayúscula inicial ("Mensajes de contacto"), como se escribe
 * en español, en lugar de capitalizar cada palabra.
 */
trait SentenceCaseLabels
{
    public static function getTitleCaseModelLabel(): string
    {
        return Str::ucfirst(static::getModelLabel());
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        return Str::ucfirst(static::getPluralModelLabel());
    }
}
