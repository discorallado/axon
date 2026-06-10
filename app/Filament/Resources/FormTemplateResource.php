<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FormTemplateResource\Pages;
use App\Filament\Resources\FormTemplateResource\RelationManagers\SectionsRelationManager;
use App\Models\FormTemplate;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class FormTemplateResource extends Resource
{
    protected static ?string $model = FormTemplate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Solicitudes';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'Plantilla de formulario';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Plantillas de formularios';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label(__('forms.template.fields.name'))
                ->required()
                ->maxLength(150)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set, $record) {
                    if (! $record) {
                        $set('slug', Str::slug($state));
                    }
                })
                ->columnSpanFull(),

            TextInput::make('slug')
                ->label(__('forms.template.fields.slug'))
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true)
                ->helperText('URL pública: /f/{slug}'),

            Select::make('view_type')
                ->label(__('forms.template.fields.view_type'))
                ->options(__('forms.template.view_types'))
                ->default('default')
                ->required(),

            Textarea::make('description')
                ->label(__('forms.template.fields.description'))
                ->rows(3)
                ->columnSpanFull(),

            Toggle::make('is_active')
                ->label(__('forms.template.fields.is_active'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('forms.template.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label(__('forms.template.fields.slug'))
                    ->badge()
                    ->color('gray'),

                TextColumn::make('view_type')
                    ->label(__('forms.template.fields.view_type'))
                    ->formatStateUsing(fn ($state) => __("forms.template.view_types.{$state}"))
                    ->badge(),

                TextColumn::make('current_version')
                    ->label('Versión')
                    ->prefix('v'),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),

                TextColumn::make('submissions_count')
                    ->label('Envíos')
                    ->counts('submissions'),

                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Activo'),
            ])
            ->actions([
                Action::make('copy_link')
                    ->label(__('forms.template.actions.copy_link'))
                    ->icon('heroicon-o-link')
                    ->color('gray')
                    ->action(function (FormTemplate $record) {
                        Notification::make()
                            ->title(__('forms.template.messages.link_copied'))
                            ->success()
                            ->send();
                    })
                    ->extraAttributes(fn (FormTemplate $record) => [
                        'x-data' => '',
                        'x-on:click' => "navigator.clipboard.writeText('{$record->publicUrl()}')",
                    ]),

                EditAction::make(),

                DeleteAction::make()
                    ->visible(fn (FormTemplate $record) => $record->submissions()->doesntExist()),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SectionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFormTemplates::route('/'),
            'create' => Pages\CreateFormTemplate::route('/create'),
            'edit' => Pages\EditFormTemplate::route('/{record}/edit'),
        ];
    }
}
