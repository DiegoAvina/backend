<?php

namespace App\Models\Concerns;

use App\Models\Note;
use App\Models\StatusChange;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Seguimiento comercial: responsable, notas internas e historial de estados.
 *
 * `status` y `assigned_to` no son asignables en masa: se cambian solo con
 * `CommercialTracking` (servicio), que registra el historial y la auditoría.
 */
trait TracksCommercialStatus
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return MorphMany<Note, $this>
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest()->latest('id');
    }

    /**
     * @return MorphMany<StatusChange, $this>
     */
    public function statusChanges(): MorphMany
    {
        return $this->morphMany(StatusChange::class, 'trackable')->latest('created_at')->latest('id');
    }
}
