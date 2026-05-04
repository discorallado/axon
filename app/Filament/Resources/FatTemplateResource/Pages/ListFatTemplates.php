<?php

namespace App\Filament\Resources\FatTemplateResource\Pages;

use App\Filament\Resources\FatTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFatTemplates extends ListRecords
{
    protected static string $resource = FatTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
