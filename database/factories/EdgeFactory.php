<?php

namespace Database\Factories;

use App\Models\Node;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Edge>
 */
class EdgeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'origin_node_id' => Node::factory(),
            'destination_node_id' => Node::factory(),
            'avg_lead_time_days' => fake()->numberBetween(3, 45),
            'lead_time_std_days' => fake()->numberBetween(1, 10),
            'volume' => fake()->numberBetween(10, 1000),
            'cost_per_unit' => fake()->randomFloat(2, 10, 500),
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn () => [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function between(Node $origin, Node $destination): static
    {
        return $this->state(fn () => [
            'origin_node_id' => $origin->id,
            'destination_node_id' => $destination->id,
        ]);
    }
}
