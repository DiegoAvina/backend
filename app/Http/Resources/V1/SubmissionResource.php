<?php

namespace App\Http\Resources\V1;

use App\Models\ContactMessage;
use App\Models\DemoRequest;
use App\Models\DiagnosticRequest;
use App\Models\Lead;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Acuse de recibo de un formulario. Solo confirma el registro: no devuelve
 * los datos personales enviados.
 *
 * @mixin Lead|ContactMessage|QuoteRequest|DemoRequest|DiagnosticRequest
 */
class SubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
