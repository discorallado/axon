<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|\UnitEnum|null $navigationGroup = 'Finanzas';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('suppliers.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('suppliers.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('suppliers.sections.details'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('suppliers.fields.name'))
                        ->required()
                        ->maxLength(255),

                    TextInput::make('rut')
                        ->label(__('suppliers.fields.rut'))
                        ->maxLength(20),

                    TextInput::make('email')
                        ->label(__('suppliers.fields.email'))
                        ->email()
                        ->maxLength(255),

                    TextInput::make('phone')
                        ->label(__('suppliers.fields.phone'))
                        ->tel()
                        ->maxLength(50),

                    TextInput::make('address')
                        ->label(__('suppliers.fields.address'))
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make('contact_name')
                        ->label(__('suppliers.fields.contact_name'))
                        ->maxLength(255),
                ])
                ->columns(2),

            Section::make(__('suppliers.sections.bank'))
                ->schema([
                    TextInput::make('bank_name')
                        ->label(__('suppliers.fields.bank_name'))
                        ->maxLength(255),

                    TextInput::make('bank_account')
                        ->label(__('suppliers.fields.bank_account'))
                        ->maxLength(255),
                ])
                ->columns(2),

            Textarea::make('notes')
                ->label(__('suppliers.fields.notes'))
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('suppliers.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('rut')
                    ->label(__('suppliers.fields.rut'))
                    ->placeholder('—'),

                TextColumn::make('contact_name')
                    ->label(__('suppliers.fields.contact_name'))
                    ->placeholder('—'),

                TextColumn::make('email')
                    ->label(__('suppliers.fields.email'))
                    ->placeholder('—')
                    ->copyable(),

                TextColumn::make('phone')
                    ->label(__('suppliers.fields.phone'))
                    ->placeholder('—'),

                TextColumn::make('purchase_orders_count')
                    ->label(__('suppliers.fields.purchase_orders_count'))
                    ->counts('purchaseOrders')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
