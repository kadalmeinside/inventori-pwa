<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\TransferRequest;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransferRequestFactory extends Factory
{
    protected $model = TransferRequest::class;

    public function definition(): array
    {
        return [
            'requester_id'      => User::factory(),
            'from_warehouse_id' => null,
            'to_warehouse_id'   => Warehouse::factory(),
            'product_id'        => Product::factory(),
            'quantity'          => fake()->numberBetween(1, 50),
            'notes'             => fake()->optional()->sentence(),
            'status'            => 'pending',
            'reviewed_by'       => null,
            'reviewed_at'       => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending', 'reviewed_by' => null, 'reviewed_at' => null]);
    }

    public function approved(): static
    {
        return $this->state([
            'status'      => 'approved',
            'reviewed_by' => User::factory()->state(['role' => 'super_admin', 'warehouse_id' => null]),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status'      => 'rejected',
            'reviewed_by' => User::factory()->state(['role' => 'super_admin', 'warehouse_id' => null]),
            'reviewed_at' => now(),
        ]);
    }
}
