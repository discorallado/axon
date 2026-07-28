<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentProjectsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Project::query()
                    ->with(['status', 'client', 'manager'])
                    ->latest()
                    ->limit(8)
            )
            ->heading(__('dashboard.recent_projects'))
            ->columns([
                TextColumn::make('code')
                    ->label(__('projects.fields.code'))
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('name')
                    ->label(__('projects.fields.name'))
                    ->wrap()
                    ->url(fn (Project $record) => ProjectResource::getUrl('view', ['record' => $record])),

                TextColumn::make('client.name')
                    ->label(__('projects.fields.client'))
                    ->placeholder('—'),

                TextColumn::make('status.name')
                    ->label(__('projects.fields.status'))
                    ->badge()
                    ->color(fn (Project $record) => $record->status?->color
                        ? Color::hex($record->status->color)
                        : 'gray'),

                TextColumn::make('manager.name')
                    ->label(__('projects.fields.manager'))
                    ->placeholder('—'),

                TextColumn::make('end_date')
                    ->label(__('projects.fields.end_date'))
                    ->date('d/m/Y')
                    ->placeholder('—'),
            ])
            ->paginated(false);
    }
}
