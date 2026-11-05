<?php

namespace App\Observers;

use App\Models\StatusChange;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

/**
 * Historial de estados de prospectos y solicitudes: registra el estado
 * inicial y cada cambio posterior, con el usuario que lo hizo.
 */
class CommercialRecordObserver
{
    public function __construct(private AuditLogger $audit) {}

    public function created(Model $model): void
    {
        $this->record($model, null);
    }

    public function updated(Model $model): void
    {
        if (! $model->wasChanged('status')) {
            return;
        }

        $from = $model->getOriginal('status');
        $this->record($model, $from instanceof \BackedEnum ? $from->value : $from);

        $this->audit->log(
            'status_changed',
            $model,
            'Cambio de estado comercial',
            ['status' => $from instanceof \BackedEnum ? $from->value : $from],
            ['status' => $model->getAttribute('status')?->value],
        );
    }

    private function record(Model $model, ?string $from): void
    {
        /** @var MorphMany<StatusChange, Model> $relation */
        $relation = $model->statusChanges();

        $relation->create([
            'from_status' => $from,
            'to_status' => $model->getAttribute('status')?->value,
            'user_id' => Auth::id(),
        ]);
    }
}
