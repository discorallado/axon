<?php

namespace App\Filament\Resources\FatExecutionResource\Pages;

use App\Filament\Resources\FatExecutionResource;
use App\Models\ExecutionItemResult;
use App\Models\ExecutionRevision;
use App\Repositories\ExecutionRepository;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class ExecuteFatExecution extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.resources.fat-execution-resource.pages.execute-fat-execution';

    protected static ?string $title = 'Ejecutar Checklist';

    public ?int $activeRevisionId = null;
    
    public array $results = [];
    
    public bool $isSaving = false;

    public function mount(): void
    {
        $execution = $this->getRecord();
        
        // Obtener o crear la revisión activa
        $revision = $execution->latestRevision();
        
        if (!$revision) {
            $repository = app(ExecutionRepository::class);
            $revision = $repository->createInitialRevision($execution);
        }
        
        $this->activeRevisionId = $revision->id;
        
        // Cargar resultados existentes
        $this->loadResults();
        
        $this->form->fill($this->results);
    }

    public function loadResults(): void
    {
        $revision = ExecutionRevision::with([
            'itemResults.templateItem' => function ($query) {
                $query->orderBy('path');
            }
        ])->find($this->activeRevisionId);

        if (!$revision) {
            return;
        }

        foreach ($revision->itemResults as $result) {
            $key = "item_{$result->template_item_id}";
            $this->results[$key] = [
                'result_value' => $result->result_value,
                'observations' => $result->observations,
                'numeric_value' => $result->numeric_value,
                'text_value' => $result->text_value,
            ];
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    #[On('save-result')]
    public function saveResult(int $templateItemId, array $data): void
    {
        $this->isSaving = true;
        
        try {
            $revision = ExecutionRevision::findOrFail($this->activeRevisionId);
            
            $result = ExecutionItemResult::firstOrNew([
                'revision_id' => $revision->id,
                'template_item_id' => $templateItemId,
            ]);
            
            $result->fill([
                'result_value' => $data['result_value'] ?? null,
                'observations' => $data['observations'] ?? null,
                'numeric_value' => $data['numeric_value'] ?? null,
                'text_value' => $data['text_value'] ?? null,
            ]);
            
            $result->save();
            
            Notification::make()
                ->title('Resultado guardado')
                ->body('El resultado se ha guardado automáticamente.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al guardar')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isSaving = false;
        }
    }

    public function submitForReview(): void
    {
        $execution = $this->getRecord();
        
        if ($execution->status === 'approved' || $execution->status === 'archived') {
            Notification::make()
                ->title('No se puede enviar a revisión')
                ->body('Esta ejecución ya está aprobada o archivada.')
                ->warning()
                ->send();
            return;
        }
        
        $execution->update(['status' => 'pending_review']);
        
        Notification::make()
            ->title('Enviado a revisión')
            ->body('El protocolo ha sido enviado para revisión.')
            ->success()
            ->send();
    }

    public function getCompletionPercentageAttribute(): float
    {
        $execution = $this->getRecord();
        $revision = $execution->latestRevision();
        
        if (!$revision) {
            return 0.0;
        }
        
        $totalItems = $revision->template->items()->count();
        
        if ($totalItems === 0) {
            return 0.0;
        }
        
        $completedItems = $revision->itemResults()
            ->whereNotNull('result_value')
            ->count();
        
        return round(($completedItems / $totalItems) * 100, 2);
    }

    public function getBreadcrumb(): string
    {
        return 'Ejecutar Checklist';
    }

    public function getViewData(): array
    {
        $execution = $this->getRecord();
        $revision = ExecutionRevision::with([
            'itemResults.evidences',
            'itemResults.history' => function ($query) {
                $query->latest()->limit(5);
            },
            'template.sections' => function ($query) {
                $query->orderBy('order');
            }
        ])->find($this->activeRevisionId);

        return [
            'execution' => $execution,
            'revision' => $revision,
            'sections' => $revision?->template?->sections ?? collect(),
            'completionPercentage' => $this->completionPercentage,
        ];
    }
}
