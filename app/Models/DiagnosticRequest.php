<?php

namespace App\Models;

use App\Enums\ContactMethod;
use App\Models\Concerns\LeadSubmission;
use Database\Factories\DiagnosticRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Solicitud de diagnóstico. `solution_id` es nulo cuando el prospecto pide
 * orientación sin un tema definido.
 */
#[Fillable([
    'lead_id', 'solution_id', 'company', 'contact_method', 'availability', 'message',
    'idempotency_key', 'ip_hash', 'user_agent', 'utm', 'consent_at',
])]
class DiagnosticRequest extends Model
{
    /** @use HasFactory<DiagnosticRequestFactory> */
    use HasFactory, LeadSubmission;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contact_method' => ContactMethod::class,
        ];
    }

    /**
     * @return BelongsTo<Solution, $this>
     */
    public function solution(): BelongsTo
    {
        return $this->belongsTo(Solution::class);
    }
}
