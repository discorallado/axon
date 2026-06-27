<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Exports\TasksExport;
use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\ProjectMembersRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\TasksRelationManager;
use Filament\Actions\Action;
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
                ->label(__('projects.views.kanban'))
                ->icon('heroicon-o-view-columns')
                ->color('gray')
                ->url(fn () => ProjectResource::getUrl('kanban', ['record' => $this->record])),

            Action::make('gantt')
                ->label(__('projects.views.gantt'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->color('gray')
                ->url(fn () => ProjectResource::getUrl('gantt', ['record' => $this->record])),

            Action::make('export_xlsx')
                ->label(__('projects.export.xlsx'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => Excel::download(
                    new TasksExport($this->record),
                    'tareas-'.$this->record->code.'.xlsx'
                )),

            Action::make('export_csv')
                ->label(__('projects.export.csv'))
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->action(fn () => Excel::download(
                    new TasksExport($this->record),
                    'tareas-'.$this->record->code.'.csv',
                    \Maatwebsite\Excel\Excel::CSV,
                    ['Content-Type' => 'text/csv']
                )),

            EditAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            ActivitiesRelationManager::class,
            TasksRelationManager::class,
            ProjectMembersRelationManager::class,
        ];
    }
}
