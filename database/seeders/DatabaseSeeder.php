<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockMovementService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(StockMovementService $stockService): void
    {
        // 1. Create Warehouses
        $jakarta = Warehouse::factory()->create([
            'name'    => 'Gudang Pusat Jakarta',
            'address' => 'Jakarta Selatan',
        ]);
        
        $bandung = Warehouse::factory()->create([
            'name'    => 'Cabang Bandung',
            'address' => 'Bandung, Jawa Barat',
        ]);

        // 2. Create Users
        User::factory()->superAdmin()->create([
            'name'  => 'Super Admin',
            'email' => 'admin@inventori.test',
        ]);

        User::factory()->create([
            'name'         => 'Admin Jakarta',
            'email'        => 'jakarta@inventori.test',
            'role'         => 'branch_admin',
            'warehouse_id' => $jakarta->id,
        ]);

        User::factory()->create([
            'name'         => 'Admin Bandung',
            'email'        => 'bandung@inventori.test',
            'role'         => 'branch_admin',
            'warehouse_id' => $bandung->id,
        ]);

        // 3. Create Products
        $products = Product::factory()->count(10)->create();

        // 4. Inject Initial Stock
        // Use StockMovementService to properly create logs
        $superAdmin = User::where('role', 'super_admin')->first();
        foreach ($products as $product) {
            // Random stock for Jakarta
            $stockService->stockIn(
                warehouse: $jakarta,
                product: $product,
                quantity: rand(50, 500),
                performedBy: $superAdmin,
                notes: 'Initial Seeding'
            );

            // Random stock for Bandung
            $stockService->stockIn(
                warehouse: $bandung,
                product: $product,
                quantity: rand(10, 100),
                performedBy: $superAdmin,
                notes: 'Initial Seeding'
            );
        }
    }
}
