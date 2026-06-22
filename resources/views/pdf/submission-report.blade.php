<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Solicitud {{ $submission->reference_code }}</title>
    <style>
        /* ── Página ── */
        @page {
            margin-top: 15mm;
            margin-right: 16mm;
            margin-bottom: 22mm;
            margin-left: 16mm;
        }

        body {
            font-family: Montserrat, DejaVu Sans, sans-serif;
            font-size: 10pt;
            font-weight: medium !important;
            color: #1a1a1a;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        /* ── Pie de página fijo (aparece en cada página) ── */
        .footer {
            position: fixed;
            bottom: -15mm;
            left: 0;
            right: 0;
            border-top: 1px solid #d1d5db;
            padding-top: 4px;
            font-size: 8pt;
            color: #303235;
            text-align: center;
        }

        /* ── Cabecera ── */
        .header {
            display: table;
            width: 100%;
            padding-bottom: 12px;
            margin-bottom: 14px;
            border-bottom: 2px solid #5a5e65;
        }

        .header-left {
            display: table-cell;
            width: 55%;
            vertical-align: middle;
        }

        .header-right {
            display: table-cell;
            width: 45%;
            vertical-align: middle;
            text-align: right;
        }

        .company-logo {
            display: block;
        }

        .company-sub {
            font-size: 10pt;
            font-weight: bold;
            color: #111827;
            margin-top: 10px;
            margin-left: 15px;
        }

        .doc-ref {
            font-size: 11pt;
            font-weight: bold;
            color: #111827;
            text-align: right;
        }

        /* ── Franja resumen ── */
        .summary-strip {
            display: table;
            width: 100%;
            border: 1px solid #7f7f84;
            margin-bottom: 16px;
            background: #d6d7d8;
        }

        .summary-item {
            display: table-cell;
            width: 25%;
            padding: 8px 10px;
            border-right: 1px solid #7f7f84;
            vertical-align: top;
            text-align: center;
        }

        .summary-item-last {
            display: table-cell;
            width: 25%;
            padding: 8px 10px;
            vertical-align: top;
            text-align: center;
        }

        .summary-label {
            font-size: 8pt;
            color: #111827;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .summary-value {
            font-size: 10pt;
            font-weight: bold;
            color: #111827;
        }

        /* ── Badge de estado ── */
        .badge {
            display: inline-block;
            padding: 1px 7px;
            font-size: 9pt;
            font-weight: bold;
        }

        .badge-nueva {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge-en_revision {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-cotizada {
            background: #ede9fe;
            color: #5b21b6;
        }

        .badge-aprobada {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-rechazada {
            background: #fee2e2;
            color: #991b1b;
        }

        /* ── Secciones ── */
        .section {
            margin-bottom: 14px;
            page-break-inside: avoid;
        }

        .section-title {
            background: #1e3a5f;
            color: #ffffff;
            font-size: 10pt;
            font-weight: bold;
            padding: 5px 8px;
            margin-bottom: 7px;
            line-height: 1;
        }

        .subsection-title {
            font-size: 10pt;
            font-weight: bold;
            color: #1d447b;
            padding: 3px 6px;
            margin-top: 8px;
            margin-bottom: 4px;
            border-left: 3px solid #1d447b;
            background: #eff6ff;
        }

        /* ── Tablas de datos ── */
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;

        }

        table.data th {
            background: #f3f4f6;
            color: #374151;
            font-size: 10pt;
            font-weight: bold;
            text-align: left;
            padding: 4px 7px;
            border: 1px solid #e5e7eb;
            width: auto;
            max-width: 30%;

        }

        table.data td {
            font-size: 10pt;
            padding: 4px 7px;
            border: 1px solid #e5e7eb;
            color: #111827;
            max-width: 30%;

        }

        /* ── Tarjeta de tablero ── */
        .board-card {
            border: 1px solid #a7a9ad;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        .board-card-header {
            background: #2a63b2;
            color: #ffffff;
            padding: 6px 10px;
            font-size: 10pt;
            font-weight: bold;
            line-height: 1;
        }

        .board-kpis {
            display: table;
            width: 100%;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
        }

        .kpi {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 8px 4px;
            border-right: 1px solid #e5e7eb;
        }

        .kpi-last {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 8px 4px;
        }

        .kpi-label {
            display: block;
            font-size: 10pt;
            text-transform: uppercase;
            color: #000;
            margin-bottom: 3px;
        }

        .kpi-value {
            display: block;
            font-size: 10pt;
            font-weight: bold;
            color: #1d447b;
        }

        .board-card-body {
            padding: 8px 10px;
        }

        /* ── Historial ── */
        table.history {
            width: 100%;
            border-collapse: collapse;
        }

        table.history thead th {
            background: #f3f4f6;
            color: #000;
            font-size: 10pt;
            font-weight: bold;
            text-align: left;
            vertical-align: middle;
            padding: 4px 7px;
            border: 1px solid #9a9da2;
        }

        table.history tbody td {
            font-size: 10pt;
            padding: 4px 7px;
            border: 1px solid #c7cbd1;
            vertical-align: top;
        }

        table.history tbody tr.row-even td {
            background: #f9fafb;
        }

        /* ── Adjuntos ── */
        .attachment-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .attachment-list li {
            font-size: 10pt;
            padding: 3px 0 3px 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #000;
        }

        .text-muted {
            color: #000 !important;
            font-style: italic;
            font-size: 9pt;
            line-height: 1;
        }
    </style>
</head>

<body>

    {{-- Pie de página repetido en cada hoja --}}
    <div class="footer">
        Documento generado el {{ now()->setTimezone('America/Santiago')->format('d/m/Y H:i') }}
        &nbsp;·&nbsp; {{ $submission->reference_code }}
        &nbsp;·&nbsp; Uso interno — confidencial
    </div>

    {{-- ══ CABECERA ══ --}}
    <div class="header">
        <div class="header-left">
            <img src="{{ public_path('storage/images/logo_cse_dark.png') }}"
                alt="CS Energy" class="company-logo"
                width="220" height="auto">
            <div class="company-sub">SOLUCIONES INDUSTRIALES</div>
        </div>
        <div class="header-right">
            <div class="doc-ref">Ref: {{ $submission->reference_code }}</div>
        </div>
    </div>

    {{-- ══ FRANJA RESUMEN ══ --}}
    <div class="summary-strip">
        <div class="summary-item">
            <div class="summary-label">Proyecto</div>
            <div class="summary-value">{{ $submission->project_name ?? 'Sin registro' }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Empresa</div>
            <div class="summary-value">{{ $submission->submitter_company ?? 'Sin registro' }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Tableros solicitados</div>
            <div class="summary-value">{{ $submission->items->count() }}</div>
        </div>
        <div class="summary-item-last">
            <div class="summary-label">Entrega deseada</div>
            <div class="summary-value">{{ $submission->desired_delivery_date?->format('d/m/Y') ?? 'N/A' }}</div>
        </div>
    </div>

    {{-- ══ 1. DATOS DEL SOLICITANTE ══ --}}
    <div class="section">
        <div class="section-title">1. Datos del Solicitante</div>
        <table class="data">
            <tr>
                <th>Nombre</th>
                <td>{{ $submission->submitter_name ?: '' }}
                    @if(!$submission->submitter_name)<span class="text-muted">Sin registro</span>@endif
                </td>
            </tr>
            <tr>
                <th>Correo electrónico</th>
                <td>{{ $submission->submitter_email ?: '' }}
                    @if(!$submission->submitter_email)<span class="text-muted">Sin registro</span>@endif
                </td>
            </tr>
            <tr>
                <th>Teléfono</th>
                <td>{{ $submission->submitter_phone ?: '' }}
                    @if(!$submission->submitter_phone)<span class="text-muted">Sin registro</span>@endif
                </td>
            </tr>
        </table>
    </div>

    {{-- ══ 2. DATOS DEL PROYECTO ══ --}}
    <div class="section">
        <div class="section-title">2. Datos del Proyecto</div>
        <table class="data">
            <tr>
                <th>Nombre del proyecto/obra</th>
                <td colspan="3">{{ $submission->project_name ?: '' }}
                    @if(!$submission->project_name)<span class="text-muted">Sin registro</span>@endif
                </td>
            </tr>
            <tr>
                <th>Empresa</th>
                <td>{{ $submission->submitter_company ?: '' }}
                    @if(!$submission->submitter_company)<span class="text-muted">Sin registro</span>@endif
                </td>
            </tr>
            <tr>
                <th>Ubicación de la instalación</th>
                <td>{{ $submission->installation_location ?: '' }}
                    @if(!$submission->installation_location)<span class="text-muted">Sin registro</span>@endif
                </td>
            </tr>
            <tr>
                <th>Centro de costo</th>
                <td>{{ $submission->cost_center ?: '' }}
                    @if(!$submission->cost_center)<span class="text-muted">Sin registro</span>@endif
                </td>
            </tr>
            <tr>
                <th>Fecha de entrega deseada</th>
                <td>{{ $submission->desired_delivery_date?->format('d/m/Y') ?? '' ?: '' }}
                    @if(!$submission->desired_delivery_date)<span class="text-muted">Sin registro</span>@endif
                </td>
            </tr>
            <tr>
                <th>Ingeniería básica</th>
                <td>
                    @php
                    $engLabels = [
                    'csenergia' => 'CS Energía se encarga',
                    'cliente' => 'La entrega el cliente',
                    'conjunta' => 'Conjunta (CS Energía + cliente)',
                    ];
                    @endphp
                    {{ $engLabels[$submission->engineering_by ?? ''] ?? ($submission->engineering_by ?? '') ?: '' }}
                    @if(!$submission->engineering_by)<span class="text-muted">Sin registro</span>@endif
                </td>
            </tr>
            @if($submission->project_observations)
            <tr>
                <th>Observaciones generales</th>
                <td colspan="3">{{ $submission->project_observations }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- ══ 3. ESTADO DE LA SOLICITUD ══ --}}
    <div class="section">
        <div class="section-title">3. Estado de la Solicitud</div>
        <table class="data">
            <tr>
                <th>Estado al momento de generar</th>
                <td>
                    <span class="badge badge-{{ $submission->status->value }}">{{ $submission->status->getLabel() }}</span>
                    <span style="font-size:10pt; color:#1e3a5f; margin-left:8px;line-height:1.3rem;">
                        al {{ now()->setTimezone('America/Santiago')->format('d/m/Y \a \l\a\s H:i') }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>Enviado el</th>
                <td>{{ $submission->submitted_at?->setTimezone('America/Santiago')->format('d/m/Y H:i') ?? '' }}
                    @if(!$submission->submitted_at)<span class="text-muted">Sin registro</span>@endif
                </td>
            </tr>
            <tr>
                <th>Responsable asignado</th>
                <td>{{ $submission->assignee?->name ?? '' }}
                    @if(!$submission->assignee)<span class="text-muted">Sin asignar</span>@endif
                </td>
            </tr>
        </table>
    </div>

    {{-- ══ 4. TABLEROS ══ --}}
    <div class="section">
        <div class="section-title">4. Tableros de la Solicitud — {{ $submission->items->count() }} tablero(s)</div>

        @forelse($submission->items as $index => $item)
        <div class="board-card">
            <div class="board-card-header">
                Tablero {{ $index + 1 }}: {{ $item->label ?? 'Sin nombre' }}
                &nbsp;·&nbsp; {{ $item->quantity ?? 1 }} unidad(es)
            </div>

            {{-- KPIs rápidos --}}
            <div class="board-kpis">
                <div class="kpi">
                    <span class="kpi-label">Tensión</span>
                    <span class="kpi-value">
                        @if($item->supply_voltage === 'otro') {{ $item->supply_voltage_other }}
                        @elseif($item->supply_voltage) {{ $item->supply_voltage }}
                        @else —
                        @endif
                        <span style="font-size:8pt; font-weight:normal; color:#374151;">V</span>
                    </span>
                </div>
                <div class="kpi">
                    <span class="kpi-label">Corriente</span>
                    <span class="kpi-value">
                        {{ $item->nominal_current ?? '—' }}
                        <span style="font-size:8pt; font-weight:normal; color:#374151;">A</span>
                    </span>
                </div>
                <div class="kpi">
                    <span class="kpi-label">Grado IP</span>
                    <span class="kpi-value">{{ $item->ip_rating ?? '—' }}</span>
                </div>
                <div class="kpi-last">
                    <span class="kpi-label">Cantidad</span>
                    <span class="kpi-value">{{ $item->quantity ?? 1 }}</span>
                </div>
            </div>

            <div class="board-card-body">

                {{-- Identificación --}}
                <div class="subsection-title">Identificación</div>
                <table class="data">
                    <tr>
                        <th>Tipo de tablero</th>
                        <td>{{ $item->boardTypeLabel() }}</td>
                        <th>¿Qué se requiere?</th>
                        <td>
                            @php $deliveryLabels = ['tablero' => 'Tablero completo', 'gabinete' => 'Solo gabinete', 'reparacion' => 'Reparación/modificación']; @endphp
                            {{ $deliveryLabels[$item->delivery_type ?? ''] ?? ($item->delivery_type ?? '') ?: '' }}
                            @if(!$item->delivery_type)<span class="text-muted">Sin registro</span>@endif
                        </td>
                    </tr>
                    <tr>
                        <th>Tipo de instalación</th>
                        <td>
                            @php $instLabels = ['nueva' => 'Nueva instalación', 'reemplazo' => 'Reemplazo', 'ampliacion' => 'Ampliación']; @endphp
                            {{ $instLabels[$item->is_new_installation ?? ''] ?? ($item->is_new_installation ?? '') ?: '' }}
                            @if(!$item->is_new_installation)<span class="text-muted">Sin registro</span>@endif
                        </td>
                        <th>N.° de salidas/circuitos</th>
                        <td>{{ $item->number_of_circuits ?: '' }}
                            @if(!$item->number_of_circuits)<span class="text-muted">Sin registro</span>@endif
                        </td>
                    </tr>
                    @if($item->board_function)
                    <tr>
                        <th>Función principal</th>
                        <td colspan="3">{{ $item->board_function }}</td>
                    </tr>
                    @endif
                    @if($item->loads_to_feed)
                    <tr>
                        <th>Cargas a alimentar</th>
                        <td colspan="3">{{ $item->loads_to_feed }}</td>
                    </tr>
                    @endif
                </table>

                {{-- Instalación y Entorno --}}
                <div class="subsection-title">Instalación y Entorno</div>
                <table class="data">
                    <tr>
                        <th>Ubicación</th>
                        <td>
                            @if($item->location_type)
                            @php $locLabel = match($item->location_type) { 'interior' => 'Interior', 'exterior' => 'Exterior', default => $item->location_type }; @endphp
                            {{ $locLabel }}
                            @else
                            <span class="text-muted">Sin registro</span>
                            @endif
                        </td>
                        <th>Grado IP</th>
                        <td>{{ $item->ip_rating ?: '' }}
                            @if(!$item->ip_rating)<span class="text-muted">Sin registro</span>@endif
                        </td>
                    </tr>
                    <tr>
                        <th>Grado IK</th>
                        <td>{{ $item->ik_rating ?: '' }}
                            @if(!$item->ik_rating)<span class="text-muted">Sin registro</span>@endif
                        </td>
                        <th>Ambiente especial</th>
                        <td>
                            @if($item->special_environment && count($item->special_environment))
                            {{ implode(', ', $item->special_environment) }}
                            @else
                            <span class="text-muted">Sin registro</span>
                            @endif
                        </td>
                    </tr>
                    @if($item->has_dimension_restrictions)
                    <tr>
                        <th>Dimensiones máximas</th>
                        <td colspan="3">
                            Alto: {{ $item->max_height ?? '—' }} mm &nbsp;·&nbsp;
                            Ancho: {{ $item->max_width ?? '—' }} mm &nbsp;·&nbsp;
                            Prof.: {{ $item->max_depth ?? '—' }} mm
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <th>Montaje</th>
                        <td colspan="3">
                            @php
                            $mountLabels = [
                            'autosoportado' => 'Autosoportado',
                            'mural' => 'Mural/pared',
                            'rack_19' => 'Rack 19"',
                            'pedestal' => 'Pedestal',
                            'otro' => 'Otro',
                            ];
                            @endphp
                            {{ $mountLabels[$item->mounting_type ?? ''] ?? ($item->mounting_type ?? '') ?: '' }}
                            @if(!$item->mounting_type)<span class="text-muted">Sin registro</span>@endif
                        </td>
                    </tr>
                    @if($item->additional_installation_conditions)
                    <tr>
                        <th>Condiciones adicionales</th>
                        <td colspan="3">{{ $item->additional_installation_conditions }}</td>
                    </tr>
                    @endif
                </table>

                {{-- Especificaciones Eléctricas --}}
                <div class="subsection-title">Especificaciones Eléctricas</div>
                <table class="data">
                    <tr>
                        <th>Tensión de alimentación</th>
                        <td>
                            @if($item->supply_voltage === 'otro') {{ $item->supply_voltage_other }} V
                            @elseif($item->supply_voltage) {{ $item->supply_voltage }} V
                            @else <span class="text-muted">Sin registro</span>
                            @endif
                        </td>
                        <th>Sistema eléctrico</th>
                        <td>
                            @php
                            $sysLabels = [
                            'trifasico' => 'Trifásico (3F+N)',
                            'bifasico' => 'Bifásico (2F)',
                            'monofasico' => 'Monofásico (1F+N)',
                            'dc' => 'Corriente continua (DC)',
                            'otro' => $item->electrical_system_other ?? 'Otro',
                            ];
                            @endphp
                            {{ $sysLabels[$item->electrical_system ?? ''] ?? ($item->electrical_system ?? '') ?: '' }}
                            @if(!$item->electrical_system)<span class="text-muted">Sin registro</span>@endif
                        </td>
                    </tr>
                    <tr>
                        <th>Potencia estimada</th>
                        <td>
                            @if($item->estimated_power) {{ $item->estimated_power }} {{ $item->power_unit }}
                            @else <span class="text-muted">Sin registro</span>
                            @endif
                        </td>
                        <th>Corriente nominal</th>
                        <td>
                            @if($item->nominal_current) {{ $item->nominal_current }} A
                            @else <span class="text-muted">Sin registro</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Frecuencia</th>
                        <td colspan="3">
                            @if($item->frequency === 'otro') {{ $item->other_frequency }} Hz
                            @elseif($item->frequency) {{ $item->frequency }} Hz
                            @else <span class="text-muted">Sin registro</span>
                            @endif
                        </td>
                    </tr>
                    @if($item->required_protections && count($item->required_protections))
                    <tr>
                        <th>Protecciones requeridas</th>
                        <td colspan="3">{{ implode(', ', $item->required_protections) }}</td>
                    </tr>
                    @endif
                    @if($item->preferred_brands && count($item->preferred_brands))
                    <tr>
                        <th>Marcas preferidas</th>
                        <td colspan="3">{{ implode(', ', $item->preferred_brands) }}</td>
                    </tr>
                    @endif
                </table>

                {{-- Diseño Constructivo --}}
                <div class="subsection-title">Diseño Constructivo</div>
                <table class="data">
                    <tr>
                        <th>Material del gabinete</th>
                        <td>{{ $item->cabinet_material ?: '' }}
                            @if(!$item->cabinet_material)<span class="text-muted">Sin registro</span>@endif
                        </td>
                        <th>Color</th>
                        <td>
                            @php
                            $colorLabels = [
                            '7035' => 'RAL 7035 — Gris claro',
                            '7016' => 'RAL 7016 — Gris antracita',
                            '9016' => 'RAL 9016 — Blanco tráfico',
                            '9005' => 'RAL 9005 — Negro intenso',
                            '5010' => 'RAL 5010 — Azul genciana',
                            '6005' => 'RAL 6005 — Verde musgo',
                            'otro' => 'Otro',
                            ];
                            @endphp
                            {{ $colorLabels[$item->special_color ?? ''] ?? ($item->special_color ?? '') ?: '' }}
                            @if(!$item->special_color)<span class="text-muted">Sin registro</span>@endif
                        </td>
                    </tr>
                    <tr>
                        <th>Ventilación</th>
                        <td>
                            @php
                            $ventLabels = [
                            'natural' => 'Natural (rejillas)',
                            'forzada' => 'Forzada (ventiladores)',
                            'sellado' => 'Sellado',
                            'climatizado' => 'Climatizado',
                            ];
                            @endphp
                            {{ $ventLabels[$item->ventilation_type ?? ''] ?? ($item->ventilation_type ?? '') ?: '' }}
                            @if(!$item->ventilation_type)<span class="text-muted">Sin registro</span>@endif
                        </td>
                        <th>Expansión futura</th>
                        <td>
                            @php
                            $expLabels = [
                            'no' => 'Sin espacio adicional',
                            '10' => '~10% espacio libre',
                            '20' => '~20% espacio libre',
                            '30' => '~30% espacio libre',
                            'otro'=> 'Otro porcentaje',
                            ];
                            @endphp
                            {{ $expLabels[$item->future_expansion ?? ''] ?? ($item->future_expansion ?? '') ?: '' }}
                            @if(!$item->future_expansion)<span class="text-muted">Sin registro</span>@endif
                        </td>
                    </tr>
                </table>

                {{-- Adjuntos del ítem --}}
                @php
                $itemAttachments = $item->attachments;
                $attTagLabels = [
                'load_list' => 'Lista de cargas',
                'unilineal_diagram' => 'Diagrama unilineal',
                'mechanical_plans' => 'Planos mecánicos',
                ];
                @endphp
                @if($itemAttachments->isNotEmpty())
                <div class="subsection-title">Adjuntos del tablero</div>
                <ul class="attachment-list">
                    @foreach($itemAttachments as $att)
                    <li>{{ $attTagLabels[$att->tag] ?? $att->tag }}: {{ $att->original_name }}</li>
                    @endforeach
                </ul>
                @endif

                @if($item->additional_observations)
                <div class="subsection-title">Observaciones del tablero</div>
                <p style="font-size:8.5pt; padding:4px 7px; color:#374151;">{{ $item->additional_observations }}</p>
                @endif

            </div>
        </div>
        @empty
        <p class="text-muted" style="padding:4px 6px;">Sin tableros registrados.</p>
        @endforelse
    </div>

    {{-- ══ 5. DOCUMENTACIÓN ADJUNTA ══ --}}
    @php
    $projectAttachments = $submission->attachments;
    $projTagLabels = [
    'technical_specs' => 'Especificaciones técnicas',
    'site_photo' => 'Fotografía del sitio',
    ];
    @endphp
    <div class="section">
        <div class="section-title">5. Documentación Adjunta del Proyecto</div>
        @if($projectAttachments->isNotEmpty())
        <ul class="attachment-list">
            @foreach($projectAttachments as $att)
            <li>{{ $projTagLabels[$att->tag] ?? $att->tag }}: {{ $att->original_name }}</li>
            @endforeach
        </ul>
        @else
        <p class="text-muted" style="padding:4px 6px;">Sin adjuntos registrados.</p>
        @endif
    </div>

    {{-- ══ 6. HISTORIAL DE ESTADOS ══ --}}
    <div class="section">
        <div class="section-title">6. Historial de Estados</div>
        @if($submission->statusHistories->isNotEmpty())
        <table class="history">
            <thead>
                <tr>
                    <th style="width:18%">Fecha</th>
                    <th style="width:22%">Usuario</th>
                    <th style="width:15%">Desde</th>
                    <th style="width:15%">Hacia</th>
                    <th>Comentario</th>
                </tr>
            </thead>
            <tbody>
                @foreach($submission->statusHistories->sortBy('created_at') as $i => $history)
                <tr class="{{ $i % 2 === 1 ? 'row-even' : '' }}">
                    <td>{{ $history->created_at->setTimezone('America/Santiago')->format('d/m/Y H:i') }}</td>
                    <td>{{ $history->changedBy?->name ?? 'Sistema' }}</td>
                    <td>{{ $history->from_status?->getLabel() ?? '—' }}</td>
                    <td>{{ $history->to_status->getLabel() }}</td>
                    <td>{{ $history->comment ?? '' }}&nbsp;</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="text-muted" style="padding:4px 6px;">Sin historial registrado.</p>
        @endif
    </div>

</body>

</html>