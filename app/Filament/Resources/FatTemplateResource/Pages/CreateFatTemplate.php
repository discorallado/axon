<?php

namespace App\Filament\Resources\FatTemplateResource\Pages;

use App\Filament\Resources\FatTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFatTemplate extends CreateRecord
{
    protected static string $resource = FatTemplateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Plantilla FAT creada exitosamente';
    }
}
