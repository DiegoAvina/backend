<?php

namespace App\Http\Requests\Api\V1\Concerns;

use Illuminate\Validation\Validator;

/**
 * Rechaza parámetros de consulta que el listado no admite.
 *
 * `#[FailOnUnknownFields]` solo revisa el cuerpo de la petición; en un GET
 * los filtros llegan en la *query string*, así que se validan aquí.
 */
trait RejectsUnknownQueryParameters
{
    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $allowed = array_keys($this->rules());

                foreach (array_keys($this->query()) as $parameter) {
                    if (! in_array($parameter, $allowed, true)) {
                        $validator->errors()->add((string) $parameter, "El parámetro {$parameter} no está permitido.");
                    }
                }
            },
        ];
    }
}
