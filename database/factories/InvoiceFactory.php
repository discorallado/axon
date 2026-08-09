<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Organization;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $org = Organization::factory()->create();
        $net = $this->faker->randomFloat(2, 100000, 5000000);
        $tax = round($net * 0.19, 2);

        return [
            'organization_id' => $org->id,
            'type' => InvoiceType::Incoming,
            'client_id' => null,
            'supplier_id' => Supplier::factory()->state(['organization_id' => $org->id]),
            'project_id' => null,
            'purchase_order_id' => null,
            'code' => 'FC-'.now()->year.'-'.str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 3, '0', STR_PAD_LEFT),
            'number' => $this->faker->optional()->numerify('F-#####'),
            'date' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'due_date' => $this->faker->dateTimeBetween('now', '+2 months'),
            'currency' => Currency::CLP,
            'amount_net' => $net,
            'tax_amount' => $tax,
            'amount_total' => $net + $tax,
            'status' => InvoiceStatus::Pendiente,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => now()->subDays(5),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Pagada,
            'payment_date' => now(),
        ]);
    }
}
