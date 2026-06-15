<?php

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubmissionRequestFactory extends Factory
{
    public function definition(): array
    {
        $org = Organization::factory()->create();

        return [
            'organization_id' => $org->id,
            'reference_code' => 'SOL-'.now()->year.'-'.str_pad($this->faker->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'status' => SubmissionStatus::Nueva,
            'submitter_name' => $this->faker->name(),
            'submitter_email' => $this->faker->safeEmail(),
            'submitter_phone' => $this->faker->optional()->phoneNumber(),
            'submitter_company' => $this->faker->optional()->company(),
            'submitted_at' => now(),
        ];
    }
}
