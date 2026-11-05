<?php

namespace App\Filament\Widgets;

use App\Models\Note;
use App\Models\StatusChange;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

/**
 * Actividad comercial reciente: cambios de estado y notas. No muestra el
 * contenido de las notas, solo que se registró una.
 */
class RecentActivityWidget extends TableWidget
{
    protected static ?int $sort = 100;

    public static function canView(): bool
    {
        return Auth::user()?->can('dashboard.commercial') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Actividad reciente')
            ->records(fn (): array => static::canView() ? $this->activity() : [])
            ->columns([
                TextColumn::make('description')->label('Actividad')->wrap(),
                TextColumn::make('user')->label('Usuario'),
                TextColumn::make('created_at')->label('Cuándo')->since(),
            ])
            ->paginated(false)
            ->emptyStateHeading('Sin actividad');
    }

    /**
     * @return array<string, array{description: string, user: string, created_at: mixed}>
     */
    private function activity(): array
    {
        $changes = StatusChange::query()->with('user')->latest('created_at')->limit(10)->get()
            ->map(fn (StatusChange $change): array => [
                'key' => 'status-'.$change->id,
                'description' => class_basename($change->trackable_type).' #'.$change->trackable_id.': '
                    .($change->from_status?->label() ?? 'recibido').' → '.$change->to_status->label(),
                'user' => $change->user?->name ?? 'Sitio web',
                'created_at' => $change->created_at,
            ]);

        $notes = Note::query()->with('author')->latest()->limit(10)->get()
            ->map(fn (Note $note): array => [
                'key' => 'note-'.$note->id,
                'description' => 'Nota en '.class_basename($note->notable_type).' #'.$note->notable_id,
                'user' => $note->author?->name ?? '—',
                'created_at' => $note->created_at,
            ]);

        return $changes->concat($notes)->sortByDesc('created_at')->take(10)->keyBy('key')->all();
    }
}
