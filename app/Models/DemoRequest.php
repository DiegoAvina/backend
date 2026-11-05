<?php

namespace App\Models;

use App\Enums\CompanySize;
use App\Models\Concerns\LeadSubmission;
use Database\Factories\DemoRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Solicitud de demo o de acceso anticipado a un producto.
 */
#[Fillable([
    'lead_id', 'product_id', 'company', 'company_size', 'role', 'message',
    'idempotency_key', 'ip_hash', 'user_agent', 'utm', 'consent_at',
])]
class DemoRequest extends Model
{
    /** @use HasFactory<DemoRequestFactory> */
    use HasFactory, LeadSubmission;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'company_size' => CompanySize::class,
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
