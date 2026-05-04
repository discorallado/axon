<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FatExecution;
use App\Models\ExecutionRevision;
use App\Models\ExecutionItemResult;
use App\Models\ExecutionEvidence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * API para gestión de ejecuciones FAT
 * 
 * Esta API permite la integración con aplicaciones móviles y sistemas externos
 * para la ejecución de protocolos FAT.
 * 
 * @authenticated
 */
class FatExecutionController extends Controller
{
    /**
     * Listar ejecuciones del usuario autenticado
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * @queryParam status string Estado de las ejecuciones (draft, pending_review, approved, rejected, archived)
     * @queryParam project_id integer Filtrar por proyecto
     * @queryParam per_page integer Cantidad de resultados por página (default: 15)
     */
    public function index(Request $request): JsonResponse
    {
        $query = FatExecution::with(['project', 'template', 'latestRevision'])
            ->whereHas('template.roleSignatures', function ($q) use ($request) {
                // Filtrar ejecuciones donde el usuario tiene un rol asignado
                $q->where('role_name', $request->user()->currentTeam?->name ?? 'default');
            });

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $executions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($executions);
    }

    /**
     * Obtener detalles de una ejecución
     * 
     * @param FatExecution $execution
     * @return JsonResponse
     */
    public function show(FatExecution $execution): JsonResponse
    {
        $execution->load([
            'project',
            'template.sections' => function ($q) {
                $q->orderBy('order');
            },
            'template.sections.items' => function ($q) {
                $q->orderBy('path');
            },
            'latestRevision.itemResults' => function ($q) {
                $q->with(['evidences', 'history' => function ($qh) {
                    $qh->latest()->limit(5);
                }]);
            },
            'signatures.role',
        ]);

        return response()->json($execution);
    }

    /**
     * Obtener items de una revisión para ejecutar checklist
     * 
     * @param ExecutionRevision $revision
     * @return JsonResponse
     */
    public function getRevisionItems(ExecutionRevision $revision): JsonResponse
    {
        $items = $revision->template->items()
            ->with(['section'])
            ->orderBy('path')
            ->get()
            ->map(function ($item) use ($revision) {
                $result = $revision->itemResults()
                    ->where('template_item_id', $item->id)
                    ->first();

                return [
                    'id' => $item->id,
                    'code' => $item->code,
                    'description' => $item->description,
                    'path' => $item->path,
                    'result_type' => $item->result_type,
                    'is_required' => $item->is_required,
                    'allow_evidence' => $item->allow_evidence,
                    'properties' => $item->properties,
                    'section_title' => $item->section?->title,
                    'current_result' => $result ? [
                        'id' => $result->id,
                        'result_value' => $result->result_value,
                        'observations' => $result->observations,
                        'numeric_value' => $result->numeric_value,
                        'text_value' => $result->text_value,
                        'evidences_count' => $result->evidences()->count(),
                    ] : null,
                ];
            });

        return response()->json([
            'revision_id' => $revision->id,
            'version' => $revision->version,
            'status' => $revision->status,
            'items' => $items,
        ]);
    }

    /**
     * Registrar resultado de un item
     * 
     * @param Request $request
     * @param ExecutionRevision $revision
     * @param int $itemId
     * @return JsonResponse
     * 
     * @bodyParam result_value string required Valor del resultado (C, NC, NA)
     * @bodyParam observations string Observaciones del evaluador
     * @bodyParam numeric_value number Valor numérico para pruebas técnicas
     * @bodyParam text_value string Valor de texto libre
     */
    public function saveResult(Request $request, ExecutionRevision $revision, int $itemId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'result_value' => 'nullable|in:C,NC,NA',
            'observations' => 'nullable|string|max:65535',
            'numeric_value' => 'nullable|numeric',
            'text_value' => 'nullable|string|max:65535',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $templateItem = $revision->template->items()->findOrFail($itemId);

        $result = ExecutionItemResult::firstOrNew([
            'revision_id' => $revision->id,
            'template_item_id' => $itemId,
        ]);

        $result->fill($request->only(['result_value', 'observations', 'numeric_value', 'text_value']));
        $result->changed_by = $request->user()->id;
        $result->changed_by_name = $request->user()->name;
        $result->save();

        return response()->json([
            'message' => 'Resultado guardado exitosamente',
            'result' => $result->fresh(['evidences']),
        ]);
    }

    /**
     * Subir evidencia para un resultado
     * 
     * @param Request $request
     * @param ExecutionRevision $revision
     * @param int $resultId
     * @return JsonResponse
     * 
     * @bodyParam evidence file required Archivo de evidencia (imagen o PDF)
     */
    public function uploadEvidence(Request $request, ExecutionRevision $revision, int $resultId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'evidence' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Archivo inválido',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = ExecutionItemResult::where('revision_id', $revision->id)
            ->findOrFail($resultId);

        $file = $request->file('evidence');
        $filename = sprintf(
            'evidence_%d_%s.%s',
            $result->id,
            uniqid(),
            $file->getClientOriginalExtension()
        );

        $path = $file->storeAs(
            sprintf('fat-evidence/%d', $revision->execution_id),
            $filename,
            'public'
        );

        $evidence = ExecutionEvidence::create([
            'result_id' => $result->id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getMimeType(),
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Evidencia subida exitosamente',
            'evidence' => $evidence,
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    /**
     * Enviar ejecución a revisión
     * 
     * @param FatExecution $execution
     * @return JsonResponse
     */
    public function submitForReview(FatExecution $execution): JsonResponse
    {
        if (!in_array($execution->status, ['draft'])) {
            return response()->json([
                'message' => 'La ejecución no puede ser enviada a revisión en su estado actual',
            ], 409);
        }

        $execution->update(['status' => 'pending_review']);

        // Aquí se debería disparar una notificación a los revisores

        return response()->json([
            'message' => 'Ejecución enviada a revisión exitosamente',
            'execution' => $execution->fresh(),
        ]);
    }

    /**
     * Obtener historial de cambios de un resultado
     * 
     * @param ExecutionItemResult $result
     * @return JsonResponse
     */
    public function getResultHistory(ExecutionItemResult $result): JsonResponse
    {
        $history = $result->history()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'result_id' => $result->id,
            'history' => $history,
        ]);
    }
}
