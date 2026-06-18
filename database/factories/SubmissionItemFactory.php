<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\SubmissionRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubmissionItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'submission_request_id' => SubmissionRequest::factory(),
            'label' => $this->faker->randomElement(['TG Principal', 'T-Alumbrado', 'T-Control', 'T-Transfer']),
            'quantity' => $this->faker->numberBetween(1, 5),
            'sort_order' => 0,
            'delivery_type' => 'tablero',
            'is_new_installation' => 'nueva',
            'board_type' => $this->faker->randomElement(['fuerza', 'alumbrado', 'control', 'transfer']),
            'board_function' => $this->faker->sentence(),
            'location_type' => $this->faker->randomElement(['interior', 'exterior']),
            'ip_rating' => 'IP54',
            'ik_rating' => 'IK08',
            'mounting_type' => 'autosoportado',
            'supply_voltage' => '380',
            'electrical_system' => 'trifasico',
            'estimated_power' => $this->faker->randomFloat(2, 10, 500),
            'power_unit' => 'kW',
            'nominal_current' => $this->faker->randomFloat(1, 10, 800),
            'frequency' => '60',
            'required_protections' => ['interruptor_automatico'],
            'cabinet_material' => 'acero_pintado',
            'special_color' => '7035',
            'ventilation_type' => 'natural',
            'future_expansion' => '20',
        ];
    }
}
