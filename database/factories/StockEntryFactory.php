<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockEntry;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockEntryFactory extends Factory
{
    protected $model = StockEntry::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'product_id'   => Product::factory(),
            'quantity'     => fake()->numberBetween(0, 500),
        ];
    }

    /** State: explicitly zero stock */
    public function outOfStock(): static
    {
        return $this->state(['quantity' => 0]);
    }
}
