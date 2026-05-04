<?php

namespace App\Filament\Resources\FatTemplateResource\Pages;

use App\Filament\Resources\FatTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFatTemplate extends EditRecord
{
    protected static string $resource = FatTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('duplicate')
                ->icon('heroicon-o-document-duplicate')
                ->label('Duplicar')
                ->requiresConfirmation()
                ->action(fn () => FatTemplateResource::duplicateTemplate($this->getRecord())),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Plantilla FAT actualizada exitosamente';
    }
}
