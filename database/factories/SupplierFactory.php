<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => $this->faker->company(),
            'rut' => $this->faker->optional()->numerify('##.###.###-#'),
            'email' => $this->faker->optional()->companyEmail(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'address' => $this->faker->optional()->address(),
            'contact_name' => $this->faker->optional()->name(),
            'bank_name' => $this->faker->optional()->company(),
            'bank_account' => $this->faker->optional()->iban(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
