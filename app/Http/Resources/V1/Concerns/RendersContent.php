<?php

namespace App\Http\Resources\V1\Concerns;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Conversión segura del contenido administrable para la API.
 */
trait RendersContent
{
    /**
     * Markdown → HTML con CommonMark. Se elimina cualquier HTML escrito a mano y
     * los enlaces inseguros (`javascript:`), de modo que el frontend pueda
     * insertar el resultado sin riesgo de XSS.
     */
    protected function markdownToHtml(?string $markdown): ?string
    {
        if ($markdown === null || trim($markdown) === '') {
            return null;
        }

        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]);
    }

    /**
     * URL pública absoluta de una imagen del disco `public`.
     */
    protected function imageUrl(?string $path): ?string
    {
        return $path === null || $path === '' ? null : Storage::disk('public')->url($path);
    }
}
