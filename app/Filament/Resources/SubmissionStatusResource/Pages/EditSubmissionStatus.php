<?php

namespace App\Filament\Resources\SubmissionStatusResource\Pages;

use App\Filament\Resources\SubmissionStatusResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubmissionStatus extends EditRecord
{
    protected static string $resource = SubmissionStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
