<?php

namespace App\Filament\Resources\FatExecutionResource\Pages;

use App\Filament\Resources\FatExecutionResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewFatExecution extends ViewRecord
{
    protected static string $resource = FatExecutionResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información de Ejecución')
                    ->schema([
                        Infolists\Components\TextEntry::make('code')
                            ->label('Código'),
                        Infolists\Components\TextEntry::make('project.name')
                            ->label('Proyecto'),
                        Infolists\Components\TextEntry::make('template.name')
                            ->label('Plantilla FAT'),
                        Infolists\Components\TextEntry::make('execution_date')
                            ->label('Fecha de Ejecución')
                            ->date('d/m/Y'),
                        Infolists\Components\BadgeEntry::make('status')
                            ->label('Estado')
                            ->colors([
                                'warning' => 'draft',
                                'info' => 'pending_review',
                                'success' => 'approved',
                                'danger' => 'rejected',
                                'gray' => 'archived',
                            ]),
                        Infolists\Components\TextEntry::make('completion_percentage')
                            ->label('% Completitud')
                            ->suffix('%'),
                        Infolists\Components\TextEntry::make('comments')
                            ->label('Comentarios')
                            ->columnSpanFull(),
                    ])->columns(2),
                Infolists\Components\Section::make('Revisiones')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('revisions')
                            ->schema([
                                Infolists\Components\TextEntry::make('version')
                                    ->label('Versión')
                                    ->prefix('v'),
                                Infolists\Components\TextEntry::make('status')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Creada')
                                    ->dateTime('d/m/Y H:i'),
                                Infolists\Components\TextEntry::make('items_completed')
                                    ->label('Items Completados'),
                            ])
                            ->columns(3)
                            ->collapsible(),
                    ]),
                Infolists\Components\Section::make('Firmas')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('signatures')
                            ->schema([
                                Infolists\Components\TextEntry::make('role.name')
                                    ->label('Rol'),
                                Infolists\Components\TextEntry::make('signer_name')
                                    ->label('Nombre del Firmante'),
                                Infolists\Components\TextEntry::make('signer_title')
                                    ->label('Cargo'),
                                Infolists\Components\TextEntry::make('signed_at')
                                    ->label('Firmado el')
                                    ->dateTime('d/m/Y H:i'),
                                Infolists\Components\ImageEntry::make('signature_file_path')
                                    ->label('Firma Escaneada')
                                    ->disk('public')
                                    ->visible(fn ($record) => $record->signature_file_path !== null),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }
}
