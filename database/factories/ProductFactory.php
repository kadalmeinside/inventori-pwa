<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'sku'         => strtoupper(fake()->unique()->bothify('SKU-####')),
            'name'        => fake()->words(3, true),
            'category'    => fake()->randomElement(['Electronics', 'Clothing', 'Food', 'Tools']),
            'unit'        => fake()->randomElement(['pcs', 'kg', 'box', 'liter']),
            'min_stock'   => fake()->numberBetween(5, 20),
            'description' => fake()->sentence(),
            'is_active'   => true,
        ];
    }
}
