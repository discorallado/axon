<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectStatusResource\Pages;
use App\Models\ProjectStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectStatusResource extends Resource
{
    protected static ?string $model = ProjectStatus::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 11;

    public static function getModelLabel(): string
    {
        return __('projects.statuses.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('projects.statuses.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label(__('projects.statuses.fields.name'))
                ->required()
                ->maxLength(100),

            ColorPicker::make('color')
                ->label(__('projects.statuses.fields.color'))
                ->required(),

            TextInput::make('order')
                ->label(__('projects.statuses.fields.order'))
                ->numeric()
                ->default(0)
                ->required(),

            Toggle::make('is_completed')
                ->label(__('projects.statuses.fields.is_completed'))
                ->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order')
                    ->label(__('projects.statuses.fields.order'))
                    ->sortable(),

                ColorColumn::make('color')
                    ->label(__('projects.statuses.fields.color')),

                TextColumn::make('name')
                    ->label(__('projects.statuses.fields.name'))
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_completed')
                    ->label(__('projects.statuses.fields.is_completed'))
                    ->boolean(),

                TextColumn::make('projects_count')
                    ->label('Proyectos')
                    ->counts('projects')
                    ->badge(),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjectStatuses::route('/'),
            'create' => Pages\CreateProjectStatus::route('/create'),
            'edit' => Pages\EditProjectStatus::route('/{record}/edit'),
        ];
    }
}
