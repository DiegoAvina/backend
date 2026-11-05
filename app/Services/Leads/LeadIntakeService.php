<?php

namespace App\Services\Leads;

use App\Enums\LeadSource;
use App\Events\LeadSubmissionReceived;
use App\Models\ContactMessage;
use App\Models\DemoRequest;
use App\Models\DiagnosticRequest;
use App\Models\Lead;
use App\Models\QuoteRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Registra prospectos y sus solicitudes comerciales.
 *
 * - El prospecto se identifica por correo: si ya existe se actualizan sus
 *   datos de contacto en lugar de duplicarlo.
 * - Cada solicitud admite una llave de idempotencia: un reintento con la misma
 *   llave devuelve el registro original sin crear otro.
 */
class LeadIntakeService
{
    /**
     * Captura general de un prospecto (sin solicitud asociada).
     *
     * @param  array{name: string, email: string, phone?: ?string, company?: ?string, role?: ?string}  $lead
     */
    public function captureLead(array $lead, SubmissionContext $context): SubmissionResult
    {
        $result = DB::transaction(function () use ($lead, $context): SubmissionResult {
            $record = $this->upsertLead($lead, LeadSource::General, $context);

            return new SubmissionResult($record, $record->wasRecentlyCreated);
        });

        if ($result->created) {
            LeadSubmissionReceived::dispatch($result->record);
        }

        return $result;
    }

    /**
     * Registra una solicitud comercial y su prospecto.
     *
     * @param  class-string<ContactMessage|QuoteRequest|DemoRequest|DiagnosticRequest>  $model
     * @param  array{name: string, email: string, phone?: ?string, company?: ?string, role?: ?string}  $lead
     * @param  array<string, mixed>  $attributes
     */
    public function submit(
        string $model,
        LeadSource $source,
        array $lead,
        array $attributes,
        SubmissionContext $context,
    ): SubmissionResult {
        if ($existing = $this->findByIdempotencyKey($model, $context->idempotencyKey)) {
            return new SubmissionResult($existing, false);
        }

        try {
            $result = DB::transaction(function () use ($model, $source, $lead, $attributes, $context): SubmissionResult {
                $leadRecord = $this->upsertLead($lead, $source, $context);

                $record = $model::query()->create([
                    ...$attributes,
                    'lead_id' => $leadRecord->id,
                    'idempotency_key' => $context->idempotencyKey,
                    'ip_hash' => $context->ipHash,
                    'user_agent' => $context->userAgent,
                    'utm' => $context->utm,
                    'consent_at' => now(),
                ]);

                return new SubmissionResult($record, true);
            });

            LeadSubmissionReceived::dispatch($result->record);

            return $result;
        } catch (UniqueConstraintViolationException $exception) {
            // Dos reintentos simultáneos con la misma llave: gana el primero.
            $existing = $this->findByIdempotencyKey($model, $context->idempotencyKey);

            if ($existing === null) {
                throw $exception;
            }

            return new SubmissionResult($existing, false);
        }
    }

    /**
     * @param  array{name: string, email: string, phone?: ?string, company?: ?string, role?: ?string}  $data
     */
    private function upsertLead(array $data, LeadSource $source, SubmissionContext $context): Lead
    {
        $now = now();

        $lead = Lead::query()->createOrFirst(['email' => $data['email']], [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'role' => $data['role'] ?? null,
            'source' => $source,
            'ip_hash' => $context->ipHash,
            'utm' => $context->utm,
            'consent_at' => $now,
            'last_submitted_at' => $now,
        ]);

        if (! $lead->wasRecentlyCreated) {
            // Los datos más recientes reemplazan a los anteriores, pero un campo
            // opcional vacío no borra un dato que ya se tenía.
            $lead->fill(array_filter([
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'company' => $data['company'] ?? null,
                'role' => $data['role'] ?? null,
            ], fn (mixed $value): bool => $value !== null && $value !== ''));

            $lead->forceFill(['consent_at' => $now, 'last_submitted_at' => $now])->save();
        }

        return $lead;
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function findByIdempotencyKey(string $model, ?string $key): ?Model
    {
        if ($key === null) {
            return null;
        }

        return $model::query()->where('idempotency_key', $key)->first();
    }
}
