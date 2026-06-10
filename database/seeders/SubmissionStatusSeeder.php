<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\SubmissionStatus;
use Illuminate\Database\Seeder;

class SubmissionStatusSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::first();

        if (! $org) {
            return;
        }

        $statuses = [
            ['name' => 'Nueva',       'slug' => 'nueva',       'color' => '#3b82f6', 'sort_order' => 1, 'is_initial' => true,  'is_terminal' => false],
            ['name' => 'En revisión', 'slug' => 'en_revision', 'color' => '#f59e0b', 'sort_order' => 2, 'is_initial' => false, 'is_terminal' => false],
            ['name' => 'Cotizada',    'slug' => 'cotizada',    'color' => '#8b5cf6', 'sort_order' => 3, 'is_initial' => false, 'is_terminal' => false],
            ['name' => 'Aprobada',    'slug' => 'aprobada',    'color' => '#10b981', 'sort_order' => 4, 'is_initial' => false, 'is_terminal' => true],
            ['name' => 'Rechazada',   'slug' => 'rechazada',   'color' => '#ef4444', 'sort_order' => 5, 'is_initial' => false, 'is_terminal' => true],
        ];

        foreach ($statuses as $status) {
            SubmissionStatus::updateOrCreate(
                ['organization_id' => $org->id, 'slug' => $status['slug']],
                array_merge($status, ['organization_id' => $org->id])
            );
        }
    }
}
