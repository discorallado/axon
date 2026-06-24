<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Exports\TasksExport;
use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\ProjectMembersRelationManager;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Maatwebsite\Excel\Facades\Excel;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kanban')
                ->label('Kanban')
                ->icon('heroicon-o-squares-2x2')
                ->color('info')
                ->url(fn () => ProjectResource::getUrl('kanban', ['record' => $this->record])),

            Action::make('gantt')
                ->label('Gantt')
                ->icon('heroicon-o-chart-bar')
                ->color('success')
                ->url(fn () => ProjectResource::getUrl('gantt', ['record' => $this->record])),

            ActionGroup::make([
                Action::make('export_xlsx')
                    ->label('Exportar Excel (.xlsx)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => Excel::download(
                        new TasksExport($this->record->id),
                        'tareas-'.$this->record->code.'.xlsx'
                    )),

                Action::make('export_csv')
                    ->label('Exportar CSV (.csv)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => Excel::download(
                        new TasksExport($this->record->id),
                        'tareas-'.$this->record->code.'.csv',
                        \Maatwebsite\Excel\Excel::CSV
                    )),
            ])
                ->label('Exportar')
                ->icon('heroicon-o-arrow-down-tray')
                ->button(),

            EditAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            ActivitiesRelationManager::class,
            ProjectMembersRelationManager::class,
        ];
    }
}
