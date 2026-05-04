<?php

namespace App\Filament\Resources\FatExecutionResource\Pages;

use App\Filament\Resources\FatExecutionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFatExecutions extends ListRecords
{
    protected static string $resource = FatExecutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva Ejecución'),
        ];
    }
}
