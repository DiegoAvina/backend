<?php

namespace App\Models;

use App\Enums\BudgetRange;
use App\Enums\ProjectTimeline;
use App\Models\Concerns\LeadSubmission;
use Database\Factories\QuoteRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Solicitud de cotización. `service_id` es nulo cuando el prospecto aún no
 * tiene definido el servicio.
 */
#[Fillable([
    'lead_id', 'service_id', 'company', 'budget_range', 'timeline', 'message',
    'idempotency_key', 'ip_hash', 'user_agent', 'utm', 'consent_at',
])]
class QuoteRequest extends Model
{
    /** @use HasFactory<QuoteRequestFactory> */
    use HasFactory, LeadSubmission;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'budget_range' => BudgetRange::class,
            'timeline' => ProjectTimeline::class,
        ];
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
