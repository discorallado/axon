<?php

namespace App\Filament\Resources\FatExecutionResource\Pages;

use App\Filament\Resources\FatExecutionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFatExecution extends CreateRecord
{
    protected static string $resource = FatExecutionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('execute', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Ejecución creada';
    }

    protected function getCreatedNotificationBody(): ?string
    {
        return 'La ejecución del protocolo FAT ha sido creada. Ahora puedes comenzar a completar el checklist.';
    }
}
