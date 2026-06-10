<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubmissionStatusResource\Pages;
use App\Models\SubmissionStatus;
use Filament\Actions\DeleteAction;
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
use Illuminate\Support\Str;

class SubmissionStatusResource extends Resource
{
    protected static ?string $model = SubmissionStatus::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'Solicitudes';

    protected static ?int $navigationSort = 10;

    public static function getModelLabel(): string
    {
        return __('submissions.status.nueva'); // se sobreescribe abajo
    }

    public static function getPluralModelLabel(): string
    {
        return 'Estados de solicitud';
    }

    public static function getNavigationLabel(): string
    {
        return 'Estados';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(60)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))
                ),

            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(40)
                ->unique(ignoreRecord: true),

            ColorPicker::make('color')
                ->label('Color')
                ->required()
                ->default('#6b7280'),

            TextInput::make('sort_order')
                ->label('Orden')
                ->numeric()
                ->default(0),

            Toggle::make('is_initial')
                ->label('Estado inicial (entrada)')
                ->helperText('Solo puede haber uno por organización.'),

            Toggle::make('is_terminal')
                ->label('Estado terminal (final)')
                ->helperText('Las solicitudes en este estado no aceptan transiciones salvo reapertura.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width('50px'),

                ColorColumn::make('color')
                    ->label('Color'),

                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->badge(),

                IconColumn::make('is_initial')
                    ->label('Inicial')
                    ->boolean(),

                IconColumn::make('is_terminal')
                    ->label('Terminal')
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubmissionStatuses::route('/'),
            'create' => Pages\CreateSubmissionStatus::route('/create'),
            'edit' => Pages\EditSubmissionStatus::route('/{record}/edit'),
        ];
    }
}
