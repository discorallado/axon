<?php

namespace App\Filament\Resources;

use App\Enums\Currency;
use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Finanzas';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('purchase_orders.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('purchase_orders.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('purchase_orders.sections.details'))
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('code')
                            ->label(__('purchase_orders.fields.code'))
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Se genera automáticamente'),

                        TextInput::make('number')
                            ->label(__('purchase_orders.fields.number'))
                            ->maxLength(255),

                        DatePicker::make('date')
                            ->label(__('purchase_orders.fields.date'))
                            ->default(now())
                            ->displayFormat('d/m/Y')
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('supplier_id')
                            ->label(__('purchase_orders.fields.supplier'))
                            ->options(fn () => Supplier::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('project_id')
                            ->label(__('purchase_orders.fields.project'))
                            ->options(fn () => Project::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ]),

                    Textarea::make('description')
                        ->label(__('purchase_orders.fields.description'))
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make(__('purchase_orders.sections.amounts'))
                ->schema([
                    Grid::make(4)->schema([
                        Select::make('currency')
                            ->label(__('purchase_orders.fields.currency'))
                            ->options(Currency::class)
                            ->default(Currency::CLP)
                            ->required(),

                        TextInput::make('amount_net')
                            ->label(__('purchase_orders.fields.amount_net'))
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set, $get) => $set(
                                'amount_total',
                                round((float) ($state ?? 0) + (float) ($get('tax_amount') ?? 0), 2)
                            )),

                        TextInput::make('tax_amount')
                            ->label(__('purchase_orders.fields.tax_amount'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set, $get) => $set(
                                'amount_total',
                                round((float) ($get('amount_net') ?? 0) + (float) ($state ?? 0), 2)
                            )),

                        TextInput::make('amount_total')
                            ->label(__('purchase_orders.fields.amount_total'))
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                    ]),
                ]),

            Textarea::make('notes')
                ->label(__('purchase_orders.fields.notes'))
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('purchase_orders.fields.code'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('supplier.name')
                    ->label(__('purchase_orders.fields.supplier'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('project.name')
                    ->label(__('purchase_orders.fields.project'))
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('date')
                    ->label(__('purchase_orders.fields.date'))
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('amount_total')
                    ->label(__('purchase_orders.fields.amount_total'))
                    ->money(fn (PurchaseOrder $record) => $record->currency->value)
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('purchase_orders.fields.status'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('purchase_orders.fields.status'))
                    ->options(PurchaseOrderStatus::class),

                SelectFilter::make('project_id')
                    ->label(__('purchase_orders.fields.project'))
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('supplier_id')
                    ->label(__('purchase_orders.fields.supplier'))
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
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
            ->defaultSort('date', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('purchase_orders.sections.details'))
                ->schema([
                    Grid::make(4)->schema([
                        TextEntry::make('code')
                            ->label(__('purchase_orders.fields.code'))
                            ->weight('bold')
                            ->copyable(),

                        TextEntry::make('status')
                            ->label(__('purchase_orders.fields.status'))
                            ->badge(),

                        TextEntry::make('supplier.name')
                            ->label(__('purchase_orders.fields.supplier')),

                        TextEntry::make('project.name')
                            ->label(__('purchase_orders.fields.project'))
                            ->placeholder('—'),
                    ]),

                    Grid::make(3)->schema([
                        TextEntry::make('number')
                            ->label(__('purchase_orders.fields.number'))
                            ->placeholder('—'),

                        TextEntry::make('date')
                            ->label(__('purchase_orders.fields.date'))
                            ->date('d/m/Y'),

                        TextEntry::make('amount_total')
                            ->label(__('purchase_orders.fields.amount_total'))
                            ->money(fn (PurchaseOrder $record) => $record->currency->value),
                    ]),

                    Grid::make(2)->schema([
                        TextEntry::make('approver.name')
                            ->label(__('purchase_orders.fields.approved_by'))
                            ->placeholder('—'),

                        TextEntry::make('approved_at')
                            ->label(__('purchase_orders.fields.approved_at'))
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),
                    ]),

                    TextEntry::make('description')
                        ->label(__('purchase_orders.fields.description'))
                        ->placeholder('Sin descripción.')
                        ->columnSpanFull(),

                    TextEntry::make('notes')
                        ->label(__('purchase_orders.fields.notes'))
                        ->placeholder('Sin notas.')
                        ->columnSpanFull(),
                ]),

            Section::make(__('purchase_orders.sections.status_history'))
                ->schema([
                    RepeatableEntry::make('statusHistories')
                        ->label('')
                        ->schema([
                            Grid::make(4)->schema([
                                TextEntry::make('to_status')
                                    ->label(__('purchase_orders.fields.status'))
                                    ->badge(),

                                TextEntry::make('changedBy.name')
                                    ->label('Cambiado por')
                                    ->placeholder('Sistema'),

                                TextEntry::make('created_at')
                                    ->label('Fecha')
                                    ->dateTime('d/m/Y H:i'),

                                TextEntry::make('comment')
                                    ->label('Comentario')
                                    ->placeholder('—'),
                            ]),
                        ])
                        ->contained(false),
                ])
                ->collapsible(),

            Section::make(__('purchase_orders.sections.attachments'))
                ->schema([
                    RepeatableEntry::make('attachments')
                        ->label('')
                        ->schema([
                            Grid::make(3)->schema([
                                TextEntry::make('original_name')
                                    ->label('Archivo')
                                    ->url(fn ($record) => $record->url())
                                    ->openUrlInNewTab()
                                    ->icon('heroicon-o-paper-clip'),

                                TextEntry::make('uploader.name')
                                    ->label('Subido por')
                                    ->placeholder('—'),

                                TextEntry::make('created_at')
                                    ->label('Fecha')
                                    ->dateTime('d/m/Y H:i'),
                            ]),
                        ])
                        ->contained(false),
                ])
                ->collapsible(),
        ]);
    }

    /**
     * amount_total es editable en el form (para casos de redondeo/ajuste),
     * pero no debe poder guardarse desincronizado de amount_net + tax_amount
     * sin que el usuario lo haya tocado a propósito — se recalcula acá como
     * defensa server-side (ver QA del PR #8: el afterStateUpdated del form
     * solo sugiere el valor, no lo garantiza).
     */
    public static function recalculateAmountTotal(array $data): array
    {
        $data['amount_total'] = round((float) ($data['amount_net'] ?? 0) + (float) ($data['tax_amount'] ?? 0), 2);

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
            'view' => Pages\ViewPurchaseOrder::route('/{record}'),
        ];
    }
}
