<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Enums\TaskStatus;
use App\Filament\Resources\ProjectResource;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardResourcePage;
use Relaticle\Flowforge\Column;

class KanbanBoard extends BoardResourcePage
{
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;

    protected static ?string $title = 'Tablero Kanban';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    public function board(Board $board): Board
    {
        return $board
            ->query(fn (): Builder => Task::query()
                ->whereHas('activity', fn (Builder $q) => $q->where('project_id', $this->record->getKey()))
                ->with(['assignees:id,name', 'activity:id,name,order'])
                ->orderBy('position')
            )
            ->columnIdentifier('status')
            ->positionIdentifier('position')
            ->columns(array_map(
                fn (TaskStatus $status) => Column::enum($status),
                TaskStatus::cases()
            ))
            ->recordTitleAttribute('name')
            ->cardSchema(fn (Schema $schema): Schema => $schema->schema([
                TextEntry::make('code')
                    ->hiddenLabel()
                    ->badge()
                    ->color('gray'),
                TextEntry::make('name')
                    ->hiddenLabel()
                    ->weight('semibold'),
                TextEntry::make('priority')
                    ->hiddenLabel()
                    ->badge(),
                TextEntry::make('activity.name')
                    ->hiddenLabel()
                    ->color('gray')
                    ->size('sm'),
            ]))
            ->headerActions([
                Action::make('back_to_project')
                    ->label('Volver al proyecto')
                    ->icon('heroicon-o-arrow-left')
                    ->color('gray')
                    ->url(fn () => ProjectResource::getUrl('view', ['record' => $this->record])),
            ]);
    }
}
