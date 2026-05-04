<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FatExecutionResource\Pages;
use App\Models\FatExecution;
use App\Repositories\ExecutionRepository;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class FatExecutionResource extends Resource
{
    protected static ?string $model = FatExecution::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Protocolos FAT';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de Ejecución')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->default(fn () => FatExecution::generateCode(request()->get('project_code', ''))),
                        Forms\Components\Select::make('project_id')
                            ->relationship('project', 'name', fn ($query) => $query->active())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Proyecto')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('template_id')
                            ->relationship('template', 'name', fn ($query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Plantilla FAT')
                            ->afterStateUpdated(function (callable $set, $state) {
                                // Actualizar código basado en proyecto seleccionado
                            })
                            ->columnSpanFull(),
                        Forms\Components\DatePicker::make('execution_date')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Borrador',
                                'pending_review' => 'En Revisión',
                                'approved' => 'Aprobado',
                                'rejected' => 'Rechazado',
                                'archived' => 'Archivado',
                            ])
                            ->default('draft')
                            ->required()
                            ->disabled(fn (FatExecution $record = null): bool => $record && $record->status === 'archived'),
                        Forms\Components\Textarea::make('comments')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('project.code')
                    ->label('Proyecto')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('template.name')
                    ->label('Plantilla')
                    ->limit(30)
                    ->tooltip(fn (FatExecution $record): string => $record->template->name),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'draft',
                        'info' => 'pending_review',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'gray' => 'archived',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Borrador',
                        'pending_review' => 'En Revisión',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                        'archived' => 'Archivado',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('completion_percentage')
                    ->label('% Completitud')
                    ->numeric()
                    ->suffix('%')
                    ->color(fn (float $state): string => match (true) {
                        $state >= 100 => 'success',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    })
                    ->progressBar(),
                Tables\Columns\TextColumn::make('execution_date')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('revisions_count')
                    ->counts('revisions')
                    ->label('Revisiones'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project')
                    ->relationship('project', 'name'),
                Tables\Filters\SelectFilter::make('template')
                    ->relationship('template', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Borrador',
                        'pending_review' => 'En Revisión',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                        'archived' => 'Archivado',
                    ]),
                Tables\Filters\Filter::make('execution_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta'),
                    ])
                    ->query(function ($query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn ($q, $date): \Illuminate\Database\Eloquent\Builder => $q->whereDate('execution_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn ($q, $date): \Illuminate\Database\Eloquent\Builder => $q->whereDate('execution_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('execute')
                    ->icon('heroicon-o-pencil-square')
                    ->label('Ejecutar Checklist')
                    ->url(fn (FatExecution $record): string => Pages\ExecuteFatExecution::getUrl(['record' => $record]))
                    ->visible(fn (FatExecution $record): bool => in_array($record->status, ['draft', 'pending_review'])),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('newRevision')
                    ->icon('heroicon-o-arrow-path')
                    ->label('Nueva Revisión')
                    ->requiresConfirmation()
                    ->action(fn (FatExecution $record) => static::createNewRevision($record))
                    ->visible(fn (FatExecution $record): bool => $record->latestRevision() !== null),
                Tables\Actions\Action::make('downloadPdf')
                    ->icon('heroicon-o-document-arrow-down')
                    ->label('Descargar PDF')
                    ->action(fn (FatExecution $record) => static::downloadProtocolPdf($record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFatExecutions::route('/'),
            'create' => Pages\CreateFatExecution::route('/create'),
            'view' => Pages\ViewFatExecution::route('/{record}'),
            'edit' => Pages\EditFatExecution::route('/{record}/edit'),
            'execute' => Pages\ExecuteFatExecution::route('/{record}/execute'),
        ];
    }

    public static function createNewRevision(FatExecution $execution): void
    {
        $repository = app(ExecutionRepository::class);
        
        try {
            $previousRevision = $execution->latestRevision();
            
            if (!$previousRevision) {
                Notification::make()
                    ->title('No hay revisiones previas')
                    ->body('Primero debe ejecutar el checklist antes de crear una nueva revisión.')
                    ->warning()
                    ->send();
                return;
            }

            $newRevision = $repository->createRevisionFromPrevious($previousRevision);

            Notification::make()
                ->title('Nueva revisión creada')
                ->body("Se ha creado la revisión v{$newRevision->version} exitosamente.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al crear revisión')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function downloadProtocolPdf(FatExecution $execution): void
    {
        try {
            $pdfService = app(\App\Services\PdfGenerationService::class);
            $pdfContent = $pdfService->generateProtocolPdf($execution);
            
            $filename = sprintf('%s_Protocolo_FAT.pdf', $execution->code);
            
            return response()->streamDownload(function () use ($pdfContent) {
                echo $pdfContent;
            }, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al generar PDF')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
