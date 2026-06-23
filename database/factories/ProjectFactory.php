<?php

namespace Database\Factories;

use App\Enums\ProjectPriority;
use App\Models\Client;
use App\Models\Organization;
use App\Models\ProjectStatus;
use App\Models\SubmissionRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        $org = Organization::factory()->create();
        $prefix = strtoupper($this->faker->lexify('???'));

        return [
            'organization_id' => $org->id,
            'client_id' => Client::factory()->state(['organization_id' => $org->id]),
            'status_id' => ProjectStatus::factory()->state(['organization_id' => $org->id]),
            'code_prefix' => $prefix,
            'code' => $prefix.'-'.now()->year.'-'.str_pad($this->faker->unique()->numberBetween(1, 9999), 3, '0', STR_PAD_LEFT),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'priority' => $this->faker->randomElement(ProjectPriority::cases())->value,
            'color' => $this->faker->optional()->hexColor(),
            'start_date' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'end_date' => $this->faker->optional()->dateTimeBetween('now', '+6 months'),
        ];
    }

    public function fromSubmission(SubmissionRequest $submission): static
    {
        return $this->state([
            'submission_request_id' => $submission->id,
            'organization_id' => $submission->organization_id,
        ]);
    }
}
