<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'name'      => fake()->words(2, true) . ' Warehouse',
            'code'      => strtoupper(fake()->unique()->lexify('WH-???')),
            'address'   => fake()->streetAddress() . ', ' . fake()->city(),
            'is_active' => true,
        ];
    }
}
