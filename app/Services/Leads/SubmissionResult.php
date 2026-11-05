<?php

namespace App\Services\Leads;

use Illuminate\Database\Eloquent\Model;

/**
 * Resultado de registrar un envío. `created` es falso cuando la solicitud ya
 * existía (reintento con la misma llave de idempotencia).
 */
final readonly class SubmissionResult
{
    public function __construct(
        public Model $record,
        public bool $created,
    ) {}
}
