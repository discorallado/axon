<?php

namespace App\Services;

use App\Models\ExecutionRevision;
use App\Models\FatExecution;
use Illuminate\Support\Facades\View;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Support\Collection;

class PdfGenerationService
{
    /**
     * Generar PDF del protocolo FAT
     */
    public function generateProtocolPdf(FatExecution $execution, ?ExecutionRevision $revision = null): string
    {
        $revision = $revision ?? $execution->latestRevision();
        
        if (!$revision) {
            throw new \Exception('No hay revisiones disponibles para esta ejecución');
        }

        $data = $this->preparePdfData($execution, $revision);
        
        $html = View::make('pdf.fat-protocol', $data)->render();
        
        $pdf = DomPDF::loadHtml($html);
        $pdf->setPaper('letter', 'portrait');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        $filename = sprintf('%s_Protocolo_FAT_v%d.pdf', $execution->code, $revision->version);
        
        return $pdf->output();
    }

    /**
     * Generar PDF de auditoría
     */
    public function generateAuditLogPdf(FatExecution $execution): string
    {
        $revision = $execution->latestRevision();
        
        $historyItems = [];
        foreach ($revision->results as $result) {
            foreach ($result->history as $history) {
                $historyItems[] = [
                    'item_code' => $result->templateItem->full_code,
                    'item_description' => $result->templateItem->description,
                    'field' => $history->field_changed,
                    'old_value' => $history->formatted_old_value,
                    'new_value' => $history->formatted_new_value,
                    'changed_by' => $history->changer?->name ?? 'Sistema',
                    'changed_at' => $history->created_at->format('d/m/Y H:i'),
                ];
            }
        }

        $data = [
            'execution' => $execution,
            'project' => $execution->project,
            'template' => $execution->template,
            'historyItems' => $historyItems,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
        ];

        $html = View::make('pdf.audit-log', $data)->render();
        
        $pdf = DomPDF::loadHtml($html);
        $pdf->setPaper('letter', 'portrait');

        $filename = sprintf('%s_Auditoria.pdf', $execution->code);
        
        return $pdf->output();
    }

    /**
     * Preparar datos para el PDF
     */
    protected function preparePdfData(FatExecution $execution, ExecutionRevision $revision): array
    {
        // Obtener resultados agrupados por sección
        $resultsBySection = collect();
        
        foreach ($execution->template->sections as $section) {
            $sectionResults = $revision->results()
                ->whereHas('templateItem', fn($q) => $q->where('section_id', $section->id))
                ->with(['templateItem.children', 'evidence'])
                ->orderBy('templateItem.path')
                ->get()
                ->keyBy('template_item_id');

            $resultsBySection->put($section->id, [
                'section' => $section,
                'results' => $sectionResults,
            ]);
        }

        // Calcular estadísticas
        $statistics = $this->calculateStatistics($revision);

        // Obtener firmas
        $signatures = $revision->signatures()
            ->with('roleSignature', 'signer')
            ->orderByRaw('(SELECT approval_order FROM template_role_signatures WHERE id = execution_signatures.role_signature_id)')
            ->get();

        return [
            'execution' => $execution,
            'revision' => $revision,
            'project' => $execution->project,
            'template' => $execution->template,
            'resultsBySection' => $resultsBySection,
            'statistics' => $statistics,
            'signatures' => $signatures,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
            'symbols' => [
                'conforme' => '✓',
                'no_conforme' => '✗',
                'no_aplica' => '–',
            ],
        ];
    }

    /**
     * Calcular estadísticas para el reporte
     */
    protected function calculateStatistics(ExecutionRevision $revision): array
    {
        $results = $revision->results;
        $total = $results->count();
        $completed = $results->whereNotNull('result')->count();

        return [
            'total_items' => $total,
            'completed_items' => $completed,
            'pending_items' => $total - $completed,
            'completion_percentage' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'conforme_count' => $results->where('result', 'C')->count(),
            'no_conforme_count' => $results->where('result', 'NC')->count(),
            'no_aplica_count' => $results->where('result', 'NA')->count(),
            'with_evidence_count' => $results->where('has_evidence', true)->count(),
            'with_observations_count' => $results->whereNotNull('observations')
                ->where('observations', '!=', '')
                ->count(),
        ];
    }

    /**
     * Guardar PDF en storage
     */
    public function saveProtocolPdf(FatExecution $execution, ?ExecutionRevision $revision = null): string
    {
        $pdfContent = $this->generateProtocolPdf($execution, $revision);
        $revision = $revision ?? $execution->latestRevision();
        
        $filename = sprintf(
            'fat-executions/%d/protocol_v%d_%s.pdf',
            $execution->id,
            $revision->version,
            time()
        );

        \Storage::disk('public')->put($filename, $pdfContent);

        return $filename;
    }
}
