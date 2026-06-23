<?php

namespace App\Filament\Resources;

use App\Enums\ProjectPriority;
use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\ProjectMembersRelationManager;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\User;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static string|\UnitEnum|null $navigationGroup = 'Proyectos';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('projects.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('projects.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('projects.sections.details'))
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('code_prefix')
                            ->label(__('projects.fields.code_prefix'))
                            ->required()
                            ->maxLength(10)
                            ->helperText('Prefijo corto, ej: TAB, CSE, ELEC')
                            ->afterStateUpdated(fn ($set, $state) => $set('code_prefix', strtoupper($state)))
                            ->live(debounce: 500),

                        TextInput::make('code')
                            ->label(__('projects.fields.code'))
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Se genera automáticamente'),

                        ColorPicker::make('color')
                            ->label(__('projects.fields.color')),
                    ]),

                    TextInput::make('name')
                        ->label(__('projects.fields.name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label(__('projects.fields.description'))
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make(__('projects.sections.planning'))
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('client_id')
                            ->label(__('projects.fields.client'))
                            ->options(fn () => Client::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('status_id')
                            ->label(__('projects.fields.status'))
                            ->options(fn () => ProjectStatus::orderBy('order')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('priority')
                            ->label(__('projects.fields.priority'))
                            ->options(ProjectPriority::class)
                            ->default(ProjectPriority::Media)
                            ->required(),
                    ]),

                    Grid::make(3)->schema([
                        Select::make('manager_id')
                            ->label(__('projects.fields.manager'))
                            ->options(fn () => User::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        DatePicker::make('start_date')
                            ->label(__('projects.fields.start_date'))
                            ->displayFormat('d/m/Y'),

                        DatePicker::make('end_date')
                            ->label(__('projects.fields.end_date'))
                            ->displayFormat('d/m/Y'),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color')
                    ->label('')
                    ->width('30px'),

                TextColumn::make('code')
                    ->label(__('projects.fields.code'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('name')
                    ->label(__('projects.fields.name'))
                    ->searchable()
                    ->wrap(),

                TextColumn::make('client.name')
                    ->label(__('projects.fields.client'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status.name')
                    ->label(__('projects.fields.status'))
                    ->badge()
                    ->color(fn (Project $record) => $record->status?->color
                        ? Color::hex($record->status->color)
                        : 'gray'),

                TextColumn::make('priority')
                    ->label(__('projects.fields.priority'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('manager.name')
                    ->label(__('projects.fields.manager'))
                    ->placeholder('—'),

                TextColumn::make('end_date')
                    ->label(__('projects.fields.end_date'))
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status_id')
                    ->label(__('projects.fields.status'))
                    ->relationship('status', 'name')
                    ->preload(),

                SelectFilter::make('priority')
                    ->label(__('projects.fields.priority'))
                    ->options(ProjectPriority::class),

                SelectFilter::make('client_id')
                    ->label(__('projects.fields.client'))
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('projects.sections.details'))
                ->schema([
                    Grid::make(4)->schema([
                        TextEntry::make('code')
                            ->label(__('projects.fields.code'))
                            ->weight('bold')
                            ->copyable(),

                        TextEntry::make('status.name')
                            ->label(__('projects.fields.status'))
                            ->badge()
                            ->color(fn (Project $record) => $record->status?->color
                                ? Color::hex($record->status->color)
                                : 'gray'),

                        TextEntry::make('priority')
                            ->label(__('projects.fields.priority'))
                            ->badge(),

                        TextEntry::make('client.name')
                            ->label(__('projects.fields.client')),
                    ]),

                    Grid::make(3)->schema([
                        TextEntry::make('manager.name')
                            ->label(__('projects.fields.manager'))
                            ->placeholder('Sin asignar'),

                        TextEntry::make('start_date')
                            ->label(__('projects.fields.start_date'))
                            ->date('d/m/Y')
                            ->placeholder('—'),

                        TextEntry::make('end_date')
                            ->label(__('projects.fields.end_date'))
                            ->date('d/m/Y')
                            ->placeholder('—'),
                    ]),

                    TextEntry::make('description')
                        ->label(__('projects.fields.description'))
                        ->placeholder('Sin descripción.')
                        ->columnSpanFull(),

                    TextEntry::make('submissionRequest.reference_code')
                        ->label(__('projects.fields.submission_request'))
                        ->placeholder('—')
                        ->badge()
                        ->color('primary'),
                ]),
        ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            ActivitiesRelationManager::class,
            ProjectMembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
            'view' => Pages\ViewProject::route('/{record}'),
        ];
    }
}
