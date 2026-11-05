<?php

namespace App\Http\Requests\Api\V1;

/**
 * Captura general de un prospecto (p. ej. un futuro boletín o un CTA sin
 * formulario completo). No crea ninguna solicitud asociada.
 */
class StoreLeadRequest extends StoreSubmissionRequest
{
    protected function submissionRules(): array
    {
        return [
            'role' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function submissionAttributes(): array
    {
        return [];
    }
}
