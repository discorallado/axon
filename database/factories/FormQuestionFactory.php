<?php

namespace Database\Factories;

use App\Enums\FormQuestionType;
use App\Models\FormSection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FormQuestionFactory extends Factory
{
    public function definition(): array
    {
        $label = $this->faker->words(3, true);
        $section = FormSection::factory()->create();

        return [
            'organization_id' => $section->organization_id,
            'form_template_id' => $section->form_template_id,
            'form_section_id' => $section->id,
            'template_version' => 1,
            'key' => Str::snake(Str::ascii($label)).'_'.Str::random(4),
            'label' => ucfirst($label),
            'type' => FormQuestionType::Text,
            'options' => null,
            'is_required' => false,
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }

    public function required(): static
    {
        return $this->state(['is_required' => true]);
    }

    public function ofType(FormQuestionType $type): static
    {
        return $this->state(['type' => $type]);
    }

    public function select(array $options = []): static
    {
        return $this->state([
            'type' => FormQuestionType::Select,
            'options' => $options ?: [
                ['value' => 'a', 'label' => 'Opción A'],
                ['value' => 'b', 'label' => 'Opción B'],
            ],
        ]);
    }
}
