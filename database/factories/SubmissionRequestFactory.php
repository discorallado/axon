<?php

namespace Database\Factories;

use App\Models\FormTemplate;
use App\Models\Organization;
use App\Models\SubmissionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubmissionRequestFactory extends Factory
{
    public function definition(): array
    {
        $org = Organization::factory()->create();
        $template = FormTemplate::factory()->for($org, 'organization')->create();
        $status = SubmissionStatus::factory()->initial()->for($org, 'organization')->create();

        return [
            'organization_id' => $org->id,
            'form_template_id' => $template->id,
            'template_version' => 1,
            'reference_code' => 'SOL-'.now()->year.'-'.str_pad($this->faker->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'status_id' => $status->id,
            'submitter_name' => $this->faker->name(),
            'submitter_email' => $this->faker->safeEmail(),
            'submitter_phone' => $this->faker->optional()->phoneNumber(),
            'submitter_company' => $this->faker->optional()->company(),
            'submitted_at' => now(),
        ];
    }
}
