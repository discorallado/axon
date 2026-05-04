<?php

namespace App\Filament\Resources\FatExecutionResource\Pages;

use App\Filament\Resources\FatExecutionResource;
use Filament\Resources\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;

class EditFatExecution extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = FatExecutionResource::class;

    protected static string $view = 'filament.resources.fat-execution-resource.pages.edit-fat-execution';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'status' => $this->getRecord()->status,
            'comments' => $this->getRecord()->comments,
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        $execution = $this->getRecord();
        $execution->update([
            'status' => $data['status'],
            'comments' => $data['comments'] ?? null,
        ]);

        Notification::make()
            ->title('Ejecución actualizada')
            ->success()
            ->send();
    }

    protected function getFormSchema(): array
    {
        return [
            // Schema definido en el Resource
        ];
    }
}
