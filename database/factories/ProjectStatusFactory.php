<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectStatusFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => $this->faker->word(),
            'color' => $this->faker->hexColor(),
            'order' => $this->faker->numberBetween(1, 10),
            'is_completed' => false,
        ];
    }

    public function completed(): static
    {
        return $this->state(['is_completed' => true]);
    }
}
