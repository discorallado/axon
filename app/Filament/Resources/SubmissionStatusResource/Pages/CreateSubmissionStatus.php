<?php

namespace App\Filament\Resources\SubmissionStatusResource\Pages;

use App\Filament\Resources\SubmissionStatusResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubmissionStatus extends CreateRecord
{
    protected static string $resource = SubmissionStatusResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['organization_id'] = auth()->user()->organization_id;

        return $data;
    }
}
