<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Proyectos';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('clients.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('clients.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label(__('clients.fields.name'))
                ->required()
                ->maxLength(255),

            TextInput::make('rut')
                ->label(__('clients.fields.rut'))
                ->maxLength(20),

            TextInput::make('email')
                ->label(__('clients.fields.email'))
                ->email()
                ->maxLength(255),

            TextInput::make('phone')
                ->label(__('clients.fields.phone'))
                ->tel()
                ->maxLength(50),

            TextInput::make('address')
                ->label(__('clients.fields.address'))
                ->maxLength(255),

            TextInput::make('contact_name')
                ->label(__('clients.fields.contact_name'))
                ->maxLength(255),

            Textarea::make('notes')
                ->label(__('clients.fields.notes'))
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('clients.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('rut')
                    ->label(__('clients.fields.rut'))
                    ->placeholder('—'),

                TextColumn::make('contact_name')
                    ->label(__('clients.fields.contact_name'))
                    ->placeholder('—'),

                TextColumn::make('email')
                    ->label(__('clients.fields.email'))
                    ->placeholder('—')
                    ->copyable(),

                TextColumn::make('phone')
                    ->label(__('clients.fields.phone'))
                    ->placeholder('—'),

                TextColumn::make('projects_count')
                    ->label('Proyectos')
                    ->counts('projects')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
