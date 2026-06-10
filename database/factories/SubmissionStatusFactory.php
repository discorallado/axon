<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubmissionStatusFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => $this->faker->word(),
            'slug' => $this->faker->unique()->slug(1),
            'color' => $this->faker->hexColor(),
            'sort_order' => $this->faker->numberBetween(0, 10),
            'is_initial' => false,
            'is_terminal' => false,
        ];
    }

    public function initial(): static
    {
        return $this->state(['is_initial' => true, 'slug' => 'nueva', 'name' => 'Nueva']);
    }

    public function terminal(): static
    {
        return $this->state(['is_terminal' => true]);
    }
}
