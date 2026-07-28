<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskLink;

class TaskDependencyService
{
    /**
     * Sincroniza las tareas predecesoras de $task con la lista de ids recibida.
     * Los vínculos nuevos se crean como Fin→Inicio (type 0); los vínculos que ya
     * existían con otro tipo (definido desde el Gantt) se conservan intactos.
     * Devuelve los ids de predecesoras que se omitieron por generar un ciclo.
     */
    public static function syncPredecessors(Task $task, string $projectId, array $predecessorIds): array
    {
        $predecessorIds = array_values(array_diff(array_unique($predecessorIds), [$task->id]));

        $current = TaskLink::where('project_id', $projectId)
            ->where('target_id', $task->id)
            ->pluck('source_id')
            ->all();

        $toRemove = array_diff($current, $predecessorIds);
        $toAdd = array_diff($predecessorIds, $current);

        if ($toRemove !== []) {
            TaskLink::where('project_id', $projectId)
                ->where('target_id', $task->id)
                ->whereIn('source_id', $toRemove)
                ->delete();
        }

        $skipped = [];

        foreach ($toAdd as $predecessorId) {
            if (static::wouldCreateCycle($projectId, $predecessorId, $task->id)) {
                $skipped[] = $predecessorId;

                continue;
            }

            TaskLink::create([
                'organization_id' => $task->organization_id,
                'project_id' => $projectId,
                'source_id' => $predecessorId,
                'target_id' => $task->id,
                'type' => 0,
            ]);
        }

        return $skipped;
    }

    /**
     * ¿Agregar el vínculo $sourceId → $targetId cerraría un ciclo? Cierto si ya
     * existe un camino de $targetId hacia $sourceId en el grafo actual de la tabla
     * task_links (recorrido en anchura sobre los enlaces existentes del proyecto).
     */
    public static function wouldCreateCycle(string $projectId, string $sourceId, string $targetId): bool
    {
        if ($sourceId === $targetId) {
            return true;
        }

        $edgesByNode = TaskLink::where('project_id', $projectId)
            ->get(['source_id', 'target_id'])
            ->groupBy('source_id');

        $visited = [];
        $queue = [$targetId];

        while ($queue !== []) {
            $node = array_shift($queue);

            if ($node === $sourceId) {
                return true;
            }

            if (isset($visited[$node])) {
                continue;
            }
            $visited[$node] = true;

            foreach ($edgesByNode->get($node, collect()) as $edge) {
                $queue[] = $edge->target_id;
            }
        }

        return false;
    }
}
