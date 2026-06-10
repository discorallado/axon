<?php

namespace Database\Factories;

use App\Models\FormTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class FormSectionFactory extends Factory
{
    public function definition(): array
    {
        $template = FormTemplate::factory()->create();

        return [
            'organization_id' => $template->organization_id,
            'form_template_id' => $template->id,
            'template_version' => 1,
            'title' => $this->faker->words(2, true),
            'description' => null,
            'sort_order' => $this->faker->numberBetween(0, 5),
            'is_repeatable' => false,
        ];
    }
}
