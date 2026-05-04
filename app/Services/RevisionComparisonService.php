<?php

namespace App\Services;

use App\Models\ExecutionRevision;
use Illuminate\Support\Collection;

class RevisionComparisonService
{
    /**
     * Comparar dos revisiones y retornar diferencias
     */
    public function compareRevisions(ExecutionRevision $oldRevision, ExecutionRevision $newRevision): array
    {
        $oldResults = $oldRevision->results->keyBy('template_item_id');
        $newResults = $newRevision->results->keyBy('template_item_id');

        $changes = [];
        $unchanged = [];

        foreach ($newResults as $itemId => $newResult) {
            $oldResult = $oldResults->get($itemId);

            if (!$oldResult) {
                continue; // Item nuevo (no debería pasar en este contexto)
            }

            $diff = $this->compareItemResults($oldResult, $newResult);

            if (!empty($diff['changed_fields'])) {
                $changes[] = [
                    'item_id' => $itemId,
                    'item_code' => $newResult->templateItem->full_code,
                    'item_description' => $newResult->templateItem->description,
                    'section' => $newResult->templateItem->section->title,
                    'old_result' => $oldResult->result,
                    'new_result' => $newResult->result,
                    'changed_fields' => $diff['changed_fields'],
                    'details' => $diff['details'],
                ];
            } else {
                $unchanged[] = $itemId;
            }
        }

        return [
            'old_revision' => $oldRevision,
            'new_revision' => $newRevision,
            'total_items' => $newResults->count(),
            'changed_count' => count($changes),
            'unchanged_count' => count($unchanged),
            'changes' => $changes,
            'summary' => $this->generateSummary($changes),
        ];
    }

    /**
     * Comparar resultados de un item específico
     */
    protected function compareItemResults($oldResult, $newResult): array
    {
        $changedFields = [];
        $details = [];

        // Comparar resultado
        if ($oldResult->result !== $newResult->result) {
            $changedFields[] = 'result';
            $details['result'] = [
                'old' => $oldResult->result,
                'new' => $newResult->result,
                'old_label' => $oldResult->formatted_result,
                'new_label' => $newResult->formatted_result,
            ];
        }

        // Comparar observaciones
        if ($oldResult->observations !== $newResult->observations) {
            $changedFields[] = 'observations';
            $details['observations'] = [
                'old' => $oldResult->observations,
                'new' => $newResult->observations,
            ];
        }

        // Comparar valor numérico
        if ($oldResult->numeric_value !== $newResult->numeric_value) {
            $changedFields[] = 'numeric_value';
            $details['numeric_value'] = [
                'old' => $oldResult->numeric_value,
                'new' => $newResult->numeric_value,
            ];
        }

        // Comparar valor de texto
        if ($oldResult->text_value !== $newResult->text_value) {
            $changedFields[] = 'text_value';
            $details['text_value'] = [
                'old' => $oldResult->text_value,
                'new' => $newResult->text_value,
            ];
        }

        // Comparar evidencia
        if ($oldResult->has_evidence !== $newResult->has_evidence) {
            $changedFields[] = 'evidence';
            $details['evidence'] = [
                'old' => $oldResult->has_evidence ? 'Sí' : 'No',
                'new' => $newResult->has_evidence ? 'Sí' : 'No',
            ];
        }

        return [
            'changed_fields' => $changedFields,
            'details' => $details,
        ];
    }

    /**
     * Generar resumen de cambios
     */
    protected function generateSummary(array $changes): array
    {
        $resultChanges = [
            'to_conforme' => 0,
            'to_no_conforme' => 0,
            'to_no_aplica' => 0,
            'from_conforme' => 0,
            'from_no_conforme' => 0,
            'from_no_aplica' => 0,
        ];

        foreach ($changes as $change) {
            $old = $change['old_result'];
            $new = $change['new_result'];

            if ($new === 'C') $resultChanges['to_conforme']++;
            if ($new === 'NC') $resultChanges['to_no_conforme']++;
            if ($new === 'NA') $resultChanges['to_no_aplica']++;
            
            if ($old === 'C') $resultChanges['from_conforme']++;
            if ($old === 'NC') $resultChanges['from_no_conforme']++;
            if ($old === 'NA') $resultChanges['from_no_aplica']++;
        }

        return [
            'result_changes' => $resultChanges,
            'improvements' => $resultChanges['to_conforme'],
            'worsenings' => $resultChanges['to_no_conforme'],
            'net_change' => $resultChanges['to_conforme'] - $resultChanges['to_no_conforme'],
        ];
    }

    /**
     * Obtener items que cambiaron de Conforme a No Conforme (regresiones)
     */
    public function getRegressions(array $comparison): array
    {
        return array_filter($comparison['changes'], function ($change) {
            return $change['old_result'] === 'C' && $change['new_result'] === 'NC';
        });
    }

    /**
     * Obtener items que cambiaron de No Conforme a Conforme (mejoras)
     */
    public function getImprovements(array $comparison): array
    {
        return array_filter($comparison['changes'], function ($change) {
            return $change['old_result'] === 'NC' && $change['new_result'] === 'C';
        });
    }

    /**
     * Generar reporte visual de comparación (para vista Blade)
     */
    public function renderComparisonTable(array $comparison): Collection
    {
        return collect($comparison['changes'])->map(function ($change) {
            return [
                'code' => $change['item_code'],
                'description' => $change['item_description'],
                'section' => $change['section'],
                'old_result_symbol' => $this->getResultSymbol($change['old_result']),
                'new_result_symbol' => $this->getResultSymbol($change['new_result']),
                'old_result_class' => $this->getResultClass($change['old_result']),
                'new_result_class' => $this->getResultClass($change['new_result']),
                'changed_fields' => $change['changed_fields'],
                'has_observation_change' => in_array('observations', $change['changed_fields']),
            ];
        });
    }

    protected function getResultSymbol(?string $result): string
    {
        return match($result) {
            'C' => '✓',
            'NC' => '✗',
            'NA' => '–',
            default => '○',
        };
    }

    protected function getResultClass(?string $result): string
    {
        return match($result) {
            'C' => 'success',
            'NC' => 'danger',
            'NA' => 'gray',
            default => 'secondary',
        };
    }
}
