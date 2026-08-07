<?php

namespace App\Filament\Resources;

use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Client;
use App\Models\Invoice;
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

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static string|\UnitEnum|null $navigationGroup = 'Finanzas';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('invoices.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('invoices.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('invoices.sections.details'))
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('code')
                            ->label(__('invoices.fields.code'))
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Se genera automáticamente'),

                        TextInput::make('number')
                            ->label(__('invoices.fields.number'))
                            ->maxLength(255),

                        Select::make('type')
                            ->label(__('invoices.fields.type'))
                            ->options(InvoiceType::class)
                            ->required()
                            ->live(),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('client_id')
                            ->label(__('invoices.fields.client'))
                            ->options(fn () => Client::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => $get('type') === InvoiceType::Outgoing)
                            ->required(fn ($get) => $get('type') === InvoiceType::Outgoing),

                        Select::make('supplier_id')
                            ->label(__('invoices.fields.supplier'))
                            ->options(fn () => Supplier::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => $get('type') === InvoiceType::Incoming)
                            ->required(fn ($get) => $get('type') === InvoiceType::Incoming),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('project_id')
                            ->label(__('invoices.fields.project'))
                            ->options(fn () => Project::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Select::make('purchase_order_id')
                            ->label(__('invoices.fields.purchase_order'))
                            ->options(fn () => PurchaseOrder::pluck('code', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ]),

                    Grid::make(2)->schema([
                        DatePicker::make('date')
                            ->label(__('invoices.fields.date'))
                            ->default(now())
                            ->displayFormat('d/m/Y')
                            ->required(),

                        DatePicker::make('due_date')
                            ->label(__('invoices.fields.due_date'))
                            ->displayFormat('d/m/Y')
                            ->required()
                            ->afterOrEqual('date'),
                    ]),
                ]),

            Section::make(__('invoices.sections.amounts'))
                ->schema([
                    Grid::make(4)->schema([
                        Select::make('currency')
                            ->label(__('invoices.fields.currency'))
                            ->options(Currency::class)
                            ->default(Currency::CLP)
                            ->required(),

                        TextInput::make('amount_net')
                            ->label(__('invoices.fields.amount_net'))
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set, $get) => $set(
                                'amount_total',
                                round((float) ($state ?? 0) + (float) ($get('tax_amount') ?? 0), 2)
                            )),

                        TextInput::make('tax_amount')
                            ->label(__('invoices.fields.tax_amount'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set, $get) => $set(
                                'amount_total',
                                round((float) ($get('amount_net') ?? 0) + (float) ($state ?? 0), 2)
                            )),

                        TextInput::make('amount_total')
                            ->label(__('invoices.fields.amount_total'))
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                    ]),
                ]),

            Textarea::make('notes')
                ->label(__('invoices.fields.notes'))
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('invoices.fields.code'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('type')
                    ->label(__('invoices.fields.type'))
                    ->badge(),

                TextColumn::make('client.name')
                    ->label(__('invoices.fields.client'))
                    ->placeholder('—'),

                TextColumn::make('supplier.name')
                    ->label(__('invoices.fields.supplier'))
                    ->placeholder('—'),

                TextColumn::make('due_date')
                    ->label(__('invoices.fields.due_date'))
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('amount_total')
                    ->label(__('invoices.fields.amount_total'))
                    ->money(fn (Invoice $record) => $record->currency->value)
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('invoices.fields.status'))
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('invoices.fields.status'))
                    ->options(InvoiceStatus::class),

                SelectFilter::make('type')
                    ->label(__('invoices.fields.type'))
                    ->options(InvoiceType::class),

                SelectFilter::make('project_id')
                    ->label(__('invoices.fields.project'))
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('client_id')
                    ->label(__('invoices.fields.client'))
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('supplier_id')
                    ->label(__('invoices.fields.supplier'))
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
            ->defaultSort('due_date', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('invoices.sections.details'))
                ->schema([
                    Grid::make(4)->schema([
                        TextEntry::make('code')
                            ->label(__('invoices.fields.code'))
                            ->weight('bold')
                            ->copyable(),

                        TextEntry::make('status')
                            ->label(__('invoices.fields.status'))
                            ->badge(),

                        TextEntry::make('type')
                            ->label(__('invoices.fields.type'))
                            ->badge(),

                        TextEntry::make('number')
                            ->label(__('invoices.fields.number'))
                            ->placeholder('—'),
                    ]),

                    Grid::make(4)->schema([
                        TextEntry::make('client.name')
                            ->label(__('invoices.fields.client'))
                            ->placeholder('—'),

                        TextEntry::make('supplier.name')
                            ->label(__('invoices.fields.supplier'))
                            ->placeholder('—'),

                        TextEntry::make('project.name')
                            ->label(__('invoices.fields.project'))
                            ->placeholder('—'),

                        TextEntry::make('purchaseOrder.code')
                            ->label(__('invoices.fields.purchase_order'))
                            ->placeholder('—'),
                    ]),

                    Grid::make(4)->schema([
                        TextEntry::make('date')
                            ->label(__('invoices.fields.date'))
                            ->date('d/m/Y'),

                        TextEntry::make('due_date')
                            ->label(__('invoices.fields.due_date'))
                            ->date('d/m/Y'),

                        TextEntry::make('payment_date')
                            ->label(__('invoices.fields.payment_date'))
                            ->date('d/m/Y')
                            ->placeholder('—'),

                        TextEntry::make('amount_total')
                            ->label(__('invoices.fields.amount_total'))
                            ->money(fn (Invoice $record) => $record->currency->value),
                    ]),

                    TextEntry::make('notes')
                        ->label(__('invoices.fields.notes'))
                        ->placeholder('Sin notas.')
                        ->columnSpanFull(),
                ]),

            Section::make(__('invoices.sections.status_history'))
                ->schema([
                    RepeatableEntry::make('statusHistories')
                        ->label('')
                        ->schema([
                            Grid::make(4)->schema([
                                TextEntry::make('to_status')
                                    ->label(__('invoices.fields.status'))
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

            Section::make(__('invoices.sections.attachments'))
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
     * `client_id` y `supplier_id` son mutuamente excluyentes según `type`.
     * Se fuerza aquí (Create/Edit) en vez de un CHECK de BD — ver ADR-0011 (Q6).
     */
    public static function normalizeTypeFields(array $data): array
    {
        $type = static::resolveType($data['type'] ?? null);

        if ($type === InvoiceType::Outgoing) {
            $data['supplier_id'] = null;
        } elseif ($type === InvoiceType::Incoming) {
            $data['client_id'] = null;
        }

        return $data;
    }

    /**
     * Cuando `Select::make('type')->options(InvoiceType::class)` ya tiene
     * estado, Filament lo entrega como instancia del enum; antes de que el
     * usuario elija algo puede llegar como string o null. Se normaliza acá
     * para no depender de comparar contra ->value (bug real: ver PR #8 QA).
     */
    private static function resolveType(InvoiceType|string|null $type): ?InvoiceType
    {
        if ($type instanceof InvoiceType || $type === null) {
            return $type;
        }

        return InvoiceType::tryFrom($type);
    }

    /**
     * Filament excluye de la validación un campo que estuvo oculto en algún
     * momento del ciclo de vida del form (`isNeitherDehydratedNorValidated`),
     * así que `client_id`/`supplier_id` no se pueden dejar solo con
     * `->required(Closure)` condicional al `type` — se re-valida acá de
     * forma explícita antes de persistir. Retorna el nombre del campo
     * faltante, o null si está OK.
     */
    public static function missingRequiredTypeField(array $data): ?string
    {
        $type = static::resolveType($data['type'] ?? null);

        if ($type === InvoiceType::Outgoing && blank($data['client_id'] ?? null)) {
            return 'client';
        }

        if ($type === InvoiceType::Incoming && blank($data['supplier_id'] ?? null)) {
            return 'supplier';
        }

        return null;
    }

    /**
     * amount_total es editable en el form, pero no debe poder guardarse
     * desincronizado de amount_net + tax_amount — se recalcula acá como
     * defensa server-side (ver QA del PR #8).
     */
    public static function recalculateAmountTotal(array $data): array
    {
        $data['amount_total'] = round((float) ($data['amount_net'] ?? 0) + (float) ($data['tax_amount'] ?? 0), 2);

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }
}
