<?php

namespace App\Filament\Resources\FatTemplateResource\Pages;

use App\Filament\Resources\FatTemplateResource;
use App\Models\FatTemplate;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class PreviewFatTemplate extends ViewRecord
{
    protected static string $resource = FatTemplateResource::class;

    protected static string $view = 'filament.resources.fat-template-resource.pages.preview';

    public function mount(int | string $record): void
    {
        $this->record = app(FatTemplate::class)::findOrFail($record);
    }
}
