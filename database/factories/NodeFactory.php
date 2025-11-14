<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Node>
 */
class NodeFactory extends Factory
{
    public function definition(): array
    {
        $types = ['port', 'dc', 'plant', 'warehouse'];

        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->unique()->city().' Hub',
            'type' => fake()->randomElement($types),
            'location' => fake()->city().', '.fake()->country(),
            'capacity' => fake()->numberBetween(1_000, 50_000),
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn () => [
            'tenant_id' => $tenant->id,
        ]);
    }
}
