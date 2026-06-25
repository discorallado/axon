<?php

namespace Database\Factories;

use App\Enums\ActivityStatus;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFactory extends Factory
{
    public function definition(): array
    {
        $org = Organization::factory()->create();

        return [
            'organization_id' => $org->id,
            'project_id' => Project::factory()->state(['organization_id' => $org->id]),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'order' => $this->faker->numberBetween(1, 20),
            'status' => ActivityStatus::Pendiente,
            'start_date' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'end_date' => $this->faker->optional()->dateTimeBetween('now', '+3 months'),
        ];
    }
}
