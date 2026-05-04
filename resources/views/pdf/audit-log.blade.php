<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 9px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 14px; }
        
        .metadata { margin-bottom: 20px; }
        .metadata table { width: 100%; border-collapse: collapse; }
        .metadata td { padding: 4px; border: 1px solid #ddd; }
        .metadata .label { font-weight: bold; width: 120px; background: #f5f5f5; }
        
        .history-table { width: 100%; border-collapse: collapse; }
        .history-table th { border: 1px solid #333; padding: 5px; background: #444; color: white; font-size: 8px; }
        .history-table td { border: 1px solid #333; padding: 4px; vertical-align: top; }
        
        .item-code { font-weight: bold; width: 60px; }
        .field-change { color: #007bff; font-size: 8px; }
        .old-value { color: #dc3545; font-size: 8px; text-decoration: line-through; }
        .new-value { color: #28a745; font-size: 8px; }
        
        .footer { margin-top: 30px; text-align: center; font-size: 8px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LOG DE AUDITORÍA - CAMBIOS EN RESULTADOS</h1>
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
                <td>{{ $template->name }}</td>
                <td class="label">Generado:</td>
                <td>{{ $generatedAt }}</td>
            </tr>
        </table>
    </div>

    <table class="history-table">
        <thead>
            <tr>
                <th style="width: 60px;">Código</th>
                <th style="width: 30%;">Item</th>
                <th style="width: 100px;">Campo</th>
                <th>Valor Anterior</th>
                <th>Valor Nuevo</th>
                <th style="width: 100px;">Usuario</th>
                <th style="width: 100px;">Fecha/Hora</th>
            </tr>
        </thead>
        <tbody>
            @forelse($historyItems as $item)
                <tr>
                    <td class="item-code">{{ $item['item_code'] }}</td>
                    <td>{{ $item['item_description'] }}</td>
                    <td class="field-change">{{ $item['field'] }}</td>
                    <td class="old-value">{{ is_array($item['old_value']) ? json_encode($item['old_value'], JSON_UNESCAPED_UNICODE) : ($item['old_value'] ?? 'N/A') }}</td>
                    <td class="new-value">{{ is_array($item['new_value']) ? json_encode($item['new_value'], JSON_UNESCAPED_UNICODE) : ($item['new_value'] ?? 'N/A') }}</td>
                    <td>{{ $item['changed_by'] }}</td>
                    <td>{{ $item['changed_at'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px; color: #999;">
                        No se registraron cambios en esta ejecución
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Reporte de Auditoría | {{ $execution->code }} | Página 1 de 1
    </div>
</body>
</html>
