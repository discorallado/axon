<?php

namespace App\Filament\Resources\FormTemplateResource\Pages;

use App\Filament\Resources\FormTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFormTemplate extends CreateRecord
{
    protected static string $resource = FormTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['organization_id'] = auth()->user()->organization_id;
        $data['created_by'] = auth()->id();

        return $data;
    }
}
