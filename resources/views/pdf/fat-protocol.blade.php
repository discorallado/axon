<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 16px; }
        .header h2 { margin: 5px 0; font-size: 14px; color: #666; }
        .metadata { margin-bottom: 20px; }
        .metadata table { width: 100%; border-collapse: collapse; }
        .metadata td { padding: 4px; border: 1px solid #ddd; }
        .metadata .label { font-weight: bold; width: 150px; background: #f5f5f5; }
        
        .statistics { margin: 20px 0; }
        .statistics table { width: 100%; border-collapse: collapse; }
        .statistics th, .statistics td { border: 1px solid #333; padding: 6px; text-align: center; }
        .statistics th { background: #444; color: white; }
        
        .section { margin-top: 25px; page-break-inside: avoid; }
        .section-title { background: #333; color: white; padding: 6px; font-size: 11px; font-weight: bold; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .items-table th { border: 1px solid #333; padding: 5px; background: #666; color: white; font-size: 9px; }
        .items-table td { border: 1px solid #333; padding: 5px; vertical-align: top; }
        
        .item-code { font-weight: bold; width: 60px; }
        .item-description { width: 50%; }
        .item-result { text-align: center; width: 50px; font-size: 14px; }
        .item-observations { font-size: 9px; color: #555; }
        
        .result-c { color: #28a745; font-weight: bold; }
        .result-nc { color: #dc3545; font-weight: bold; }
        .result-na { color: #6c757d; }
        
        .evidence-indicator { font-size: 9px; color: #007bff; }
        
        .signatures { margin-top: 40px; page-break-inside: avoid; }
        .signatures h3 { border-bottom: 1px solid #333; padding-bottom: 5px; }
        .signatures-grid { display: table; width: 100%; margin-top: 15px; }
        .signature-row { display: table-row; }
        .signature-cell { 
            display: table-cell; 
            border: 1px solid #333; 
            padding: 10px; 
            text-align: center; 
            vertical-align: bottom;
            height: 100px;
            width: 33%;
        }
        .signature-line { border-top: 1px solid #333; margin-top: 40px; padding-top: 5px; }
        .signature-name { font-weight: bold; font-size: 10px; }
        .signature-role { font-size: 9px; color: #666; }
        .signature-date { font-size: 8px; color: #999; }
        
        .footer { margin-top: 30px; text-align: center; font-size: 8px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
        
        .indent-1 { padding-left: 0px; }
        .indent-2 { padding-left: 15px; }
        .indent-3 { padding-left: 30px; }
        .indent-4 { padding-left: 45px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>PROTOCOLO DE PRUEBAS FAT</h1>
        <h2>Factory Acceptance Test</h2>
    </div>

    <div class="metadata">
        <table>
            <tr>
                <td class="label">Proyecto:</td>
                <td>{{ $project->code }} - {{ $project->name }}</td>
                <td class="label">Código FAT:</td>
                <td>{{ $execution->code }}</td>
            </tr>
            <tr>
                <td class="label">Plantilla:</td>
                <td>{{ $template->code }} - {{ $template->name }}</td>
                <td class="label">Revisión:</td>
                <td>v{{ $revision->version }}</td>
            </tr>
            <tr>
                <td class="label">Cliente:</td>
                <td>{{ $project->client_name ?? 'N/A' }}</td>
                <td class="label">Fecha Ejecución:</td>
                <td>{{ $execution->execution_date?->format('d/m/Y') ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Ubicación:</td>
                <td>{{ $project->location ?? 'N/A' }}</td>
                <td class="label">Generado:</td>
                <td>{{ $generatedAt }}</td>
            </tr>
        </table>
    </div>

    <div class="statistics">
        <h3 style="margin: 0 0 10px 0;">Resumen Ejecutivo</h3>
        <table>
            <thead>
                <tr>
                    <th>Total Items</th>
                    <th>Completados</th>
                    <th>Pendientes</th>
                    <th>% Avance</th>
                    <th style="background: #28a745;">Conforme (✓)</th>
                    <th style="background: #dc3545;">No Conforme (✗)</th>
                    <th>No Aplica (–)</th>
                    <th>Con Evidencia</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>{{ $statistics['total_items'] }}</strong></td>
                    <td>{{ $statistics['completed_items'] }}</td>
                    <td>{{ $statistics['pending_items'] }}</td>
                    <td><strong>{{ $statistics['completion_percentage'] }}%</strong></td>
                    <td style="color: #28a745;"><strong>{{ $statistics['conforme_count'] }}</strong></td>
                    <td style="color: #dc3545;"><strong>{{ $statistics['no_conforme_count'] }}</strong></td>
                    <td>{{ $statistics['no_aplica_count'] }}</td>
                    <td>{{ $statistics['with_evidence_count'] }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @foreach($resultsBySection as $sectionData)
        @php
            $section = $sectionData['section'];
            $results = $sectionData['results'];
        @endphp
        
        <div class="section">
            <div class="section-title">
                SECCIÓN {{ $section->code }}: {{ $section->title }}
            </div>
            
            @if($section->description)
                <div style="padding: 5px; font-size: 9px; background: #f9f9f9; border-left: 3px solid #333;">
                    {{ $section->description }}
                </div>
            @endif

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">Código</th>
                        <th style="width: 50%;">Descripción</th>
                        <th style="width: 50px;">Resultado</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($results as $result)
                        @php
                            $item = $result->templateItem;
                            $symbol = match($result->result) {
                                'C' => $symbols['conforme'],
                                'NC' => $symbols['no_conforme'],
                                'NA' => $symbols['no_aplica'],
                                default => '○'
                            };
                            $class = match($result->result) {
                                'C' => 'result-c',
                                'NC' => 'result-nc',
                                'NA' => 'result-na',
                                default => ''
                            };
                            $indentClass = 'indent-' . min($item->depth, 4);
                        @endphp
                        <tr>
                            <td class="item-code {{ $indentClass }}">{{ $item->full_code }}</td>
                            <td class="item-description">
                                {{ $item->description }}
                                @if($item->is_required)
                                    <span style="color: #dc3545; font-size: 8px;">*</span>
                                @endif
                                @if($result->has_evidence)
                                    <div class="evidence-indicator">📎 {{ $result->evidence->count() }} archivo(s)</div>
                                @endif
                            </td>
                            <td class="item-result {{ $class }}">{{ $symbol }}</td>
                            <td class="item-observations">
                                {{ $result->observations ?? '' }}
                                @if($result->numeric_value)
                                    <div style="font-size: 8px; color: #007bff;">
                                        Valor: {{ $result->numeric_value['value'] ?? 'N/A' }} {{ $result->numeric_value['unit'] ?? '' }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    @if($signatures->count() > 0)
        <div class="signatures">
            <h3>FIRMAS DE APROBACIÓN</h3>
            <div class="signatures-grid">
                @foreach($signatures as $signature)
                    <div class="signature-row">
                        <div class="signature-cell">
                            @if($signature->signature_file_path)
                                <img src="{{ $signature->signature_url }}" style="max-height: 60px; max-width: 150px;" />
                            @else
                                <div style="height: 60px;"></div>
                            @endif
                            <div class="signature-line">
                                <div class="signature-name">{{ $signature->signer_display_name }}</div>
                                <div class="signature-role">{{ $signature->roleSignature->role_display_name }}</div>
                                <div class="signature-date">{{ $signature->formatted_signed_at }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="footer">
        Documento generado electrónicamente por Sistema FAT | {{ $execution->code }} | Página 1 de 1
    </div>
</body>
</html>
