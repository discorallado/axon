<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        $org = Organization::factory()->create();

        return [
            'organization_id' => $org->id,
            'activity_id' => Activity::factory()->state(['organization_id' => $org->id]),
            'code' => 'T-'.str_pad($this->faker->unique()->numberBetween(1, 99999), 3, '0', STR_PAD_LEFT),
            'name' => $this->faker->sentence(4),
            'description' => $this->faker->optional()->paragraph(),
            'status' => TaskStatus::Pendiente,
            'priority' => TaskPriority::Media,
            'start_date' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'due_date' => $this->faker->optional()->dateTimeBetween('now', '+2 months'),
            'estimated_hours' => $this->faker->optional()->randomFloat(1, 1, 40),
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status' => TaskStatus::Completada,
            'completed_at' => now(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state([
            'status' => TaskStatus::EnProgreso,
            'due_date' => now()->subDays(3),
        ]);
    }
}
