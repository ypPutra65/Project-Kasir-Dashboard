<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMutation;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $makananUtama = Category::where('slug', 'makanan-utama')->first();
        $minuman = Category::where('slug', 'minuman')->first();

        $products = [
            [
                'category_id' => $makananUtama?->id,
                'name' => 'Pecel Lele',
                'cost_price' => 10000.00,
                'selling_price' => 18000.00,
                'stock' => 30,
                'min_stock_alert' => 5,
                'is_active' => true,
            ],
            [
                'category_id' => $makananUtama?->id,
                'name' => 'Bebek Goreng',
                'cost_price' => 18000.00,
                'selling_price' => 30000.00,
                'stock' => 25,
                'min_stock_alert' => 5,
                'is_active' => true,
            ],
            [
                'category_id' => $makananUtama?->id,
                'name' => 'Ayam Penyet',
                'cost_price' => 13000.00,
                'selling_price' => 22000.00,
                'stock' => 25,
                'min_stock_alert' => 5,
                'is_active' => true,
            ],
            [
                'category_id' => $minuman?->id,
                'name' => 'Es Teh Manis',
                'cost_price' => 1500.00,
                'selling_price' => 5000.00,
                'stock' => 50,
                'min_stock_alert' => 10,
                'is_active' => true,
            ],
            [
                'category_id' => $minuman?->id,
                'name' => 'Es Jeruk',
                'cost_price' => 2500.00,
                'selling_price' => 7000.00,
                'stock' => 40,
                'min_stock_alert' => 10,
                'is_active' => true,
            ],
        ];

        foreach ($products as $data) {
            $product = Product::updateOrCreate(
                ['name' => $data['name']],
                $data
            );

            // Log initial stock mutation if it does not already exist
            StockMutation::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'type' => 'in',
                    'notes' => 'Stok awal menu',
                ],
                [
                    'quantity' => $product->stock,
                    'created_at' => now(),
                ]
            );
        }
    }
}
