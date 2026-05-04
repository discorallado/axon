<?php

namespace App\Repositories;

use App\Models\FatExecution;
use App\Models\ExecutionRevision;
use App\Models\ExecutionItemResult;
use App\Models\FatTemplateItem;
use App\DTOs\ItemResultDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class ExecutionRepository
{
    /**
     * Crear ejecución desde plantilla
     */
    public function createFromTemplate(FatExecution $execution): ExecutionRevision
    {
        return DB::transaction(function () use ($execution) {
            // Crear primera revisión
            $revision = ExecutionRevision::create([
                'execution_id' => $execution->id,
                'version' => 1,
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);

            // Clonar todos los items de la plantilla
            $templateItems = FatTemplateItem::whereHas('section', function ($q) use ($execution) {
                $q->where('template_id', $execution->template_id);
            })->get();

            foreach ($templateItems as $templateItem) {
                ExecutionItemResult::create([
                    'revision_id' => $revision->id,
                    'template_item_id' => $templateItem->id,
                    'result' => null,
                    'observations' => null,
                    'numeric_value' => null,
                    'text_value' => null,
                    'has_evidence' => false,
                    'created_by' => auth()->id(),
                ]);
            }

            return $revision->fresh(['results.templateItem.section']);
        });
    }

    /**
     * Crear nueva revisión desde una existente
     */
    public function createRevisionFromPrevious(ExecutionRevision $previousRevision): ExecutionRevision
    {
        return DB::transaction(function () use ($previousRevision) {
            // Crear snapshot de resultados anteriores
            $snapshot = $previousRevision->results()
                ->with('templateItem')
                ->get()
                ->map(fn($r) => [
                    'template_item_id' => $r->template_item_id,
                    'result' => $r->result,
                    'observations' => $r->observations,
                    'numeric_value' => $r->numeric_value,
                    'text_value' => $r->text_value,
                ])
                ->toArray();

            // Crear nueva revisión
            $newRevision = ExecutionRevision::create([
                'execution_id' => $previousRevision->execution_id,
                'version' => $previousRevision->version + 1,
                'is_active' => true,
                'snapshot_data' => $snapshot,
                'comments' => null,
                'created_by' => auth()->id(),
            ]);

            // Desactivar revisión anterior
            $previousRevision->update(['is_active' => false]);

            // Clonar resultados
            foreach ($previousRevision->results as $previousResult) {
                ExecutionItemResult::create([
                    'revision_id' => $newRevision->id,
                    'template_item_id' => $previousResult->template_item_id,
                    'result' => $previousResult->result,
                    'observations' => $previousResult->observations,
                    'numeric_value' => $previousResult->numeric_value,
                    'text_value' => $previousResult->text_value,
                    'has_evidence' => $previousResult->has_evidence,
                    'created_by' => auth()->id(),
                ]);
            }

            return $newRevision->fresh(['results.templateItem.section']);
        });
    }

    /**
     * Actualizar resultado de item
     */
    public function updateItemResult(ExecutionRevision $revision, int $templateItemId, ItemResultDTO $dto): ExecutionItemResult
    {
        return DB::transaction(function () use ($revision, $templateItemId, $dto) {
            $result = ExecutionItemResult::firstOrCreate(
                [
                    'revision_id' => $revision->id,
                    'template_item_id' => $templateItemId,
                ],
                [
                    'created_by' => auth()->id(),
                ]
            );

            $result->update([
                'result' => $dto->result,
                'observations' => $dto->observations,
                'numeric_value' => $dto->numericValue,
                'text_value' => $dto->textValue,
                'has_evidence' => $dto->hasEvidence,
                'updated_by' => auth()->id(),
            ]);

            return $result->fresh(['templateItem', 'evidence']);
        });
    }

    /**
     * Obtener resultados con estructura jerárquica
     */
    public function getHierarchicalResults(ExecutionRevision $revision): Collection
    {
        return $revision->results()
            ->with(['templateItem.section', 'templateItem.children', 'evidence'])
            ->join('fat_template_items', 'execution_item_results.template_item_id', '=', 'fat_template_items.id')
            ->join('fat_template_sections', 'fat_template_items.section_id', '=', 'fat_template_sections.id')
            ->where('fat_template_sections.template_id', $revision->execution->template_id)
            ->orderBy('fat_template_sections.order')
            ->orderBy('fat_template_items.path')
            ->get()
            ->groupBy(fn($r) => $r->templateItem->section->id);
    }

    /**
     * Obtener estadísticas de resultados
     */
    public function getResultStatistics(ExecutionRevision $revision): array
    {
        $results = $revision->results;
        
        return [
            'total' => $results->count(),
            'completed' => $results->whereNotNull('result')->count(),
            'pending' => $results->whereNull('result')->count(),
            'conforme' => $results->where('result', 'C')->count(),
            'no_conforme' => $results->where('result', 'NC')->count(),
            'no_aplica' => $results->where('result', 'NA')->count(),
            'with_evidence' => $results->where('has_evidence', true)->count(),
            'with_observations' => $results->whereNotNull('observations')->where('observations', '!=', '')->count(),
        ];
    }

    /**
     * Validar si todos los items requeridos están completados
     */
    public function validateRequiredItemsComplete(ExecutionRevision $revision): array
    {
        $incompleteRequired = $revision->results()
            ->whereHas('templateItem', fn($q) => $q->where('is_required', true))
            ->whereNull('result')
            ->with('templateItem.section')
            ->get();

        return [
            'is_complete' => $incompleteRequired->isEmpty(),
            'incomplete_count' => $incompleteRequired->count(),
            'incomplete_items' => $incompleteRequired,
        ];
    }
}
