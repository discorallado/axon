<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Enums\PurchaseOrderStatus;
use App\Models\Organization;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    public function definition(): array
    {
        $org = Organization::factory()->create();
        $net = $this->faker->randomFloat(2, 100000, 5000000);
        $tax = round($net * 0.19, 2);

        return [
            'organization_id' => $org->id,
            'supplier_id' => Supplier::factory()->state(['organization_id' => $org->id]),
            'project_id' => null,
            'code' => 'OC-'.now()->year.'-'.str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 3, '0', STR_PAD_LEFT),
            'number' => $this->faker->optional()->numerify('F-#####'),
            'date' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'currency' => Currency::CLP,
            'amount_net' => $net,
            'tax_amount' => $tax,
            'amount_total' => $net + $tax,
            'status' => PurchaseOrderStatus::Borrador,
            'description' => $this->faker->optional()->sentence(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function emitida(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::Emitida,
            'approved_at' => now(),
        ]);
    }
}
