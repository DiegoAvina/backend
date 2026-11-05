<?php

namespace App\Models\Concerns;

use App\Enums\RequestStatus;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Comportamiento común de las solicitudes comerciales (cotización, demo,
 * diagnóstico y contacto): pertenecen a un prospecto, tienen estado de
 * seguimiento y guardan metadatos técnicos que nunca se serializan.
 */
trait LeadSubmission
{
    /**
     * Initialize the trait.
     */
    public function initializeLeadSubmission(): void
    {
        $this->mergeCasts([
            'status' => RequestStatus::class,
            'utm' => 'array',
            'consent_at' => 'immutable_datetime',
        ]);

        $this->makeHidden(['ip_hash', 'user_agent', 'idempotency_key']);

        $this->attributes['status'] ??= RequestStatus::New->value;
    }

    /**
     * Prospecto que envió la solicitud.
     *
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
