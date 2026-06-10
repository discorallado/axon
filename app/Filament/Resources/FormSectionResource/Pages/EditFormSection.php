<?php

namespace App\Filament\Resources\FormSectionResource\Pages;

use App\Filament\Resources\FormSectionResource;
use App\Filament\Resources\FormTemplateResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditFormSection extends EditRecord
{
    protected static string $resource = FormSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_template')
                ->label('Volver a la plantilla')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => FormTemplateResource::getUrl('edit', ['record' => $this->record->form_template_id])),
        ];
    }

    public function getTitle(): string
    {
        return 'Sección: '.$this->record->title;
    }
}
