<?php

namespace App\Models;

use App\Enums\LeadSource;
use App\Enums\RequestStatus;
use App\Models\Concerns\TracksCommercialStatus;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Prospecto comercial, identificado por su correo. Contiene datos personales:
 * nunca se expone en la API pública.
 *
 * Sin borrado lógico a propósito: una solicitud de cancelación (derechos ARCO)
 * debe eliminar los datos, y sus solicitudes se borran en cascada.
 */
#[Fillable(['name', 'email', 'phone', 'company', 'role', 'source', 'ip_hash', 'utm', 'consent_at', 'last_submitted_at'])]
#[Hidden(['ip_hash'])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory, TracksCommercialStatus;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'new',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => LeadSource::class,
            'status' => RequestStatus::class,
            'utm' => 'array',
            'consent_at' => 'immutable_datetime',
            'last_submitted_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<ContactMessage, $this>
     */
    public function contactMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class);
    }

    /**
     * @return HasMany<QuoteRequest, $this>
     */
    public function quoteRequests(): HasMany
    {
        return $this->hasMany(QuoteRequest::class);
    }

    /**
     * @return HasMany<DemoRequest, $this>
     */
    public function demoRequests(): HasMany
    {
        return $this->hasMany(DemoRequest::class);
    }

    /**
     * @return HasMany<DiagnosticRequest, $this>
     */
    public function diagnosticRequests(): HasMany
    {
        return $this->hasMany(DiagnosticRequest::class);
    }
}
