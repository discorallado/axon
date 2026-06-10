<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FormTemplateFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            'organization_id' => Organization::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.Str::random(4),
            'description' => $this->faker->sentence(),
            'view_type' => 'default',
            'is_active' => true,
            'current_version' => 1,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
