<?php

namespace App\Filament\Resources\SubmissionStatusResource\Pages;

use App\Filament\Resources\SubmissionStatusResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubmissionStatuses extends ListRecords
{
    protected static string $resource = SubmissionStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
