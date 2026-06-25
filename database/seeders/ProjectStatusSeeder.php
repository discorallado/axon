<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\ProjectStatus;
use Illuminate\Database\Seeder;

class ProjectStatusSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::first();
        if (! $org) {
            return;
        }

        $statuses = [
            ['name' => 'Planificación', 'color' => '#6b7280', 'order' => 1, 'is_completed' => false],
            ['name' => 'En Ejecución', 'color' => '#3b82f6', 'order' => 2, 'is_completed' => false],
            ['name' => 'En Pausa', 'color' => '#f59e0b', 'order' => 3, 'is_completed' => false],
            ['name' => 'Completado', 'color' => '#10b981', 'order' => 4, 'is_completed' => true],
            ['name' => 'Cancelado', 'color' => '#ef4444', 'order' => 5, 'is_completed' => true],
        ];

        foreach ($statuses as $data) {
            ProjectStatus::firstOrCreate(
                ['organization_id' => $org->id, 'name' => $data['name']],
                $data + ['organization_id' => $org->id]
            );
        }
    }
}
