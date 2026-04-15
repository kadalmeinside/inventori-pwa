<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockMovementService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(StockMovementService $stockService): void
    {
        // 1. Create Branches & Warehouses
        $branches = [
            ['name' => 'Sawangan', 'code' => 'SWG', 'is_center' => true],
            ['name' => 'Pulomas', 'code' => 'PLM', 'is_center' => false],
            ['name' => 'Kingkong', 'code' => 'KKG', 'is_center' => false],
            ['name' => 'Bekasi', 'code' => 'BKS', 'is_center' => false],
            ['name' => 'Ciledug', 'code' => 'CLD', 'is_center' => false],
            ['name' => 'Bintaro', 'code' => 'BTR', 'is_center' => false],
        ];

        $warehouses = [];
        foreach ($branches as $branch) {
            $warehouses[$branch['name']] = Warehouse::create([
                'name'    => ($branch['is_center'] ? 'Pusat ' : 'Cabang ') . $branch['name'],
                'code'    => $branch['code'] . '-' . rand(100, 999), // unique code
                'address' => 'Lokasi ' . $branch['name'],
                'is_active' => true,
            ]);
        }

        $pusatWarehouse = $warehouses['Sawangan'];

        // 2. Create Users
        $superAdmin = User::factory()->superAdmin()->create([
            'name'  => 'Super Admin',
            'email' => 'admin@inventori.test',
        ]);

        foreach ($warehouses as $name => $warehouse) {
            User::factory()->create([
                'name'         => 'Admin ' . $name,
                'email'        => strtolower($name) . '@inventori.test',
                'role'         => 'branch_admin',
                'warehouse_id' => $warehouse->id,
            ]);
        }

        // 3. Create Categories
        $categories = collect(['Jersey', 'T-Shirt', 'Polo Shirt'])->mapWithKeys(function ($name) {
            return [$name => Category::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'icon' => substr(strtoupper($name), 0, 3) . '-CAT',
                'is_active' => true,
            ])];
        });

        // 4. Products Data mapping
        $apparelData = [
            [
                'base_sku' => 'JS-R-SS-2024',
                'base_name' => 'Jersey SS MERAH 2024',
                'category' => 'Jersey',
                'stock' => [
                    'XS' => 1, 'S' => 8, 'M' => 0, 'L' => 10, 'XL' => 0
                ]
            ],
            [
                'base_sku' => 'JSB-SS-2025',
                'base_name' => 'Jersey SS HITAM 2025',
                'category' => 'Jersey',
                'stock' => [
                    'XS' => 40, 'S' => 30, 'M' => 25, 'L' => 15
                ]
            ],
            [
                'base_sku' => 'JSR-SS-2025',
                'base_name' => 'Jersey SS MERAH 2025',
                'category' => 'Jersey',
                'stock' => [
                    'XS' => 54, 'S' => 42, 'M' => 34, 'L' => 29
                ]
            ],
            [
                'base_sku' => 'TS-AC-2025',
                'base_name' => 'TShirt Academy 2025',
                'category' => 'T-Shirt',
                'stock' => [
                    'S' => 2, 'M' => 13, 'L' => 9, 'XL' => 8
                ]
            ],
            [
                'base_sku' => 'PS-AC-2025',
                'base_name' => 'POLO SHIRT ACADEMY 2025',
                'category' => 'Polo Shirt',
                'stock' => [
                    'S' => 2, 'M' => 13, 'L' => 9, 'XL' => 8
                ]
            ],
            [
                'base_sku' => 'JSR-AC-2025',
                'base_name' => 'JERSEY RED ACADEMY 2025',
                'category' => 'Jersey',
                'stock' => [
                    'S' => 4, 'M' => 24, 'L' => 16, 'XL' => 12
                ]
            ],
            [
                'base_sku' => 'JSB-AC-2025',
                'base_name' => 'JERSEY BLACK ACADEMY2025',
                'category' => 'Jersey',
                'stock' => [
                    'S' => 4, 'M' => 24, 'L' => 16, 'XL' => 12
                ]
            ],
        ];

        // 5. Create specific products & insert stock
        foreach ($apparelData as $data) {
            $catId = $categories[$data['category']]->id;

            foreach ($data['stock'] as $size => $quantity) {
                // Generate a unique SKU variant (e.g. JS-R-SS-2024-XS)
                $variantSku = $data['base_sku'] . '-' . $size;
                $variantName = $data['base_name'] . ' (' . $size . ')';

                $product = Product::create([
                    'sku' => $variantSku,
                    'name' => $variantName,
                    'category_id' => $catId,
                    'unit' => 'pcs',
                    'min_stock' => 5, // Default safety threshold
                    'description' => 'Apparel - ' . $data['category'],
                    'is_active' => true,
                ]);

                // Inject initial stock to Pusat (Sawangan)
                if ($quantity > 0) {
                    $stockService->stockIn(
                        warehouse: $pusatWarehouse,
                        product: $product,
                        quantity: $quantity,
                        performedBy: $superAdmin,
                        notes: 'Initial Seeding Pusat'
                    );
                }
            }
        }
    }
}
