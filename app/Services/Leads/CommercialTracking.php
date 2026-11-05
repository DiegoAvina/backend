<?php

namespace App\Services\Leads;

use App\Enums\RequestStatus;
use App\Models\ContactMessage;
use App\Models\DemoRequest;
use App\Models\DiagnosticRequest;
use App\Models\Lead;
use App\Models\Note;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Acciones de seguimiento comercial sobre prospectos y solicitudes.
 * El historial de estados lo registra `CommercialRecordObserver`.
 */
class CommercialTracking
{
    public function __construct(private AuditLogger $audit) {}

    public function changeStatus(Lead|ContactMessage|QuoteRequest|DemoRequest|DiagnosticRequest $record, RequestStatus $status): void
    {
        if ($record->status === $status) {
            return;
        }

        $record->forceFill(['status' => $status])->save();
    }

    public function assign(Lead|ContactMessage|QuoteRequest|DemoRequest|DiagnosticRequest $record, ?User $assignee): void
    {
        if ($assignee !== null && ! $assignee->is_active) {
            throw new InvalidArgumentException('No se puede asignar a un usuario inactivo.');
        }

        $previous = $record->assigned_to;

        if ($previous === $assignee?->id) {
            return;
        }

        $record->forceFill(['assigned_to' => $assignee?->id])->save();

        $this->audit->log(
            'assigned',
            $record,
            $assignee === null ? 'Responsable retirado' : "Asignado a {$assignee->name}",
            ['assigned_to' => $previous],
            ['assigned_to' => $assignee?->id],
        );
    }

    public function addNote(Lead|ContactMessage|QuoteRequest|DemoRequest|DiagnosticRequest $record, string $body, User $author): Note
    {
        $body = trim(strip_tags($body));

        if ($body === '') {
            throw new InvalidArgumentException('La nota no puede estar vacía.');
        }

        return DB::transaction(function () use ($record, $body, $author): Note {
            $note = new Note(['body' => mb_substr($body, 0, 5000)]);
            $note->author()->associate($author);
            $record->notes()->save($note);

            $this->audit->log('note_added', $record, 'Nota interna agregada');

            return $note;
        });
    }
}
