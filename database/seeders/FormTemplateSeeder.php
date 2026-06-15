<?php

namespace Database\Seeders;

use App\Models\FormTemplate;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class FormTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('slug', 'axon-demo')->firstOrFail();

        FormTemplate::updateOrCreate(
            [
                'organization_id' => $org->id,
                'slug' => 'tableros-electricos',
            ],
            [
                'name' => 'Solicitud de Tableros Eléctricos',
                'description' => 'Complete el formulario para solicitar una cotización de tableros eléctricos.',
                'view_type' => 'wizard',
                'is_active' => true,
                'current_version' => 1,
                'created_by' => null,
            ]
        );
    }
}
