<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionItem extends Model
{
    use HasFactory, HasOrganizationScope, HasUlids;

    protected $fillable = [
        'organization_id',
        'submission_request_id',
        'label',
        'quantity',
        'sort_order',
        'delivery_type',
        'is_new_installation',
        'board_type',
        'other_board_type',
        'board_function',
        'loads_to_feed',
        'number_of_circuits',
        'load_list_file_path',
        'location_type',
        'special_environment',
        'other_special_environment',
        'ip_rating',
        'ik_rating',
        'mounting_type',
        'has_dimension_restrictions',
        'max_height',
        'max_width',
        'max_depth',
        'additional_installation_conditions',
        'supply_voltage',
        'supply_voltage_other',
        'electrical_system',
        'electrical_system_other',
        'estimated_power',
        'power_unit',
        'nominal_current',
        'frequency',
        'other_frequency',
        'required_protections',
        'preferred_brands',
        'cabinet_material',
        'special_color',
        'ventilation_type',
        'future_expansion',
        'unilineal_diagram_path',
        'mechanical_plans_path',
        'additional_observations',
    ];

    protected function casts(): array
    {
        return [
            'has_dimension_restrictions' => 'boolean',
            'special_environment' => 'array',
            'required_protections' => 'array',
            'preferred_brands' => 'array',
            'estimated_power' => 'decimal:2',
            'nominal_current' => 'decimal:2',
        ];
    }

    public function submissionRequest(): BelongsTo
    {
        return $this->belongsTo(SubmissionRequest::class);
    }

    public function boardTypeLabel(): string
    {
        $types = [
            'fuerza' => 'Tablero de Fuerza / Potencia',
            'alumbrado' => 'Tablero de Alumbrado / Distribución BT',
            'control' => 'Tablero de Control / Automatización',
            'transfer' => 'Tablero de Transferencia (ATS/MTS)',
            'sincronizacion' => 'Tablero de Sincronización de Generadores',
            'remoto' => 'Tablero de Distribución Remoto',
            'pfcs' => 'Panel de Factor de Potencia',
            'medicion' => 'Tablero de Medición / Centro de Carga',
            'variadores' => 'Tablero con Variadores de Frecuencia',
            'arrancadores' => 'Tablero con Arrancadores Suaves',
            'ups' => 'Tablero UPS / Respaldo',
            'otro' => $this->other_board_type ?? 'Otro',
        ];

        return $types[$this->board_type ?? ''] ?? ($this->board_type ?? '—');
    }
}
