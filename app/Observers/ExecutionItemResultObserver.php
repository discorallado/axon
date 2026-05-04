<?php

namespace App\Observers;

use App\Models\ExecutionItemResult;
use App\Models\ItemResultHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ExecutionItemResultObserver
{
    /**
     * Handle the ExecutionItemResult "created" event.
     */
    public function created(ExecutionItemResult $result): void
    {
        // Registrar creación en historial
        ItemResultHistory::create([
            'item_result_id' => $result->id,
            'changed_by' => Auth::id(),
            'field_changed' => 'created',
            'old_value' => null,
            'new_value' => json_encode([
                'result' => $result->result,
                'observations' => $result->observations,
            ]),
            'changed_from_ip' => Request::ip(),
        ]);
    }

    /**
     * Handle the ExecutionItemResult "updated" event.
     */
    public function updated(ExecutionItemResult $result): void
    {
        $changes = $result->getChanges();
        
        // Campos a auditar
        $auditFields = ['result', 'observations', 'numeric_value', 'text_value'];

        foreach ($auditFields as $field) {
            if (array_key_exists($field, $changes)) {
                $oldValue = $result->getOriginal($field);
                $newValue = $changes[$field];

                // Solo registrar si realmente cambió
                if ($oldValue !== $newValue) {
                    ItemResultHistory::create([
                        'item_result_id' => $result->id,
                        'changed_by' => Auth::id(),
                        'field_changed' => $field,
                        'old_value' => is_array($oldValue) ? json_encode($oldValue) : $oldValue,
                        'new_value' => is_array($newValue) ? json_encode($newValue) : $newValue,
                        'changed_from_ip' => Request::ip(),
                    ]);
                }
            }
        }
    }

    /**
     * Handle the ExecutionItemResult "deleted" event.
     */
    public function deleted(ExecutionItemResult $result): void
    {
        // Registrar eliminación
        ItemResultHistory::create([
            'item_result_id' => $result->id,
            'changed_by' => Auth::id(),
            'field_changed' => 'deleted',
            'old_value' => json_encode([
                'result' => $result->result,
                'observations' => $result->observations,
            ]),
            'new_value' => null,
            'changed_from_ip' => Request::ip(),
        ]);
    }

    /**
     * Handle the ExecutionItemResult "restored" event.
     */
    public function restored(ExecutionItemResult $result): void
    {
        // Registrar restauración
        ItemResultHistory::create([
            'item_result_id' => $result->id,
            'changed_by' => Auth::id(),
            'field_changed' => 'restored',
            'old_value' => null,
            'new_value' => json_encode([
                'result' => $result->result,
                'observations' => $result->observations,
            ]),
            'changed_from_ip' => Request::ip(),
        ]);
    }
}
