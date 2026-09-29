<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseMigrationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test all expected tables exist after migration.
     */
    public function test_all_expected_tables_exist(): void
    {
        $tables = [
            'users',
            'categories',
            'products',
            'stock_mutations',
            'orders',
            'order_items',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Table '{$table}' does not exist."
            );
        }
    }

    /**
     * Test categories table schema has all required columns.
     */
    public function test_categories_table_has_expected_columns(): void
    {
        $columns = ['id', 'name', 'slug', 'sort_order', 'is_active', 'created_at', 'updated_at'];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('categories', $column),
                "Column '{$column}' missing in 'categories' table."
            );
        }
    }

    /**
     * Test products table schema has all required columns.
     */
    public function test_products_table_has_expected_columns(): void
    {
        $columns = [
            'id', 'category_id', 'name', 'cost_price', 'selling_price',
            'image_path', 'stock', 'min_stock_alert', 'is_active',
            'created_at', 'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('products', $column),
                "Column '{$column}' missing in 'products' table."
            );
        }
    }

    /**
     * Test stock_mutations table schema has all required columns.
     */
    public function test_stock_mutations_table_has_expected_columns(): void
    {
        $columns = ['id', 'product_id', 'type', 'quantity', 'notes', 'created_at'];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('stock_mutations', $column),
                "Column '{$column}' missing in 'stock_mutations' table."
            );
        }
    }

    /**
     * Test orders table schema has all required columns.
     */
    public function test_orders_table_has_expected_columns(): void
    {
        $columns = [
            'id', 'order_number', 'total_amount', 'paid_amount',
            'change_amount', 'payment_method', 'status', 'ordered_at',
            'created_at', 'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('orders', $column),
                "Column '{$column}' missing in 'orders' table."
            );
        }
    }

    /**
     * Test order_items table schema has all required columns.
     */
    public function test_order_items_table_has_expected_columns(): void
    {
        $columns = [
            'id', 'order_id', 'product_id', 'quantity',
            'cost_price', 'unit_price', 'subtotal',
            'created_at', 'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('order_items', $column),
                "Column '{$column}' missing in 'order_items' table."
            );
        }
    }

    /**
     * Test foreign key relationships and cascade behaviors.
     */
    public function test_foreign_key_constraints_behavior(): void
    {
        // 1. Insert category
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Makanan Utama',
            'slug' => 'makanan-utama',
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Insert product
        $productId = DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'name' => 'Pecel Lele Goreng',
            'cost_price' => 10000.00,
            'selling_price' => 18000.00,
            'stock' => 20,
            'min_stock_alert' => 5,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Insert stock mutation
        DB::table('stock_mutations')->insert([
            'product_id' => $productId,
            'type' => 'in',
            'quantity' => 20,
            'notes' => 'Stok awal pagi',
            'created_at' => now(),
        ]);

        // 4. Insert order
        $orderId = DB::table('orders')->insertGetId([
            'order_number' => 'BL-20260929-0001',
            'total_amount' => 18000.00,
            'paid_amount' => 20000.00,
            'change_amount' => 2000.00,
            'payment_method' => 'cash',
            'status' => 'paid',
            'ordered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 5. Insert order item
        DB::table('order_items')->insert([
            'order_id' => $orderId,
            'product_id' => $productId,
            'quantity' => 1,
            'cost_price' => 10000.00,
            'unit_price' => 18000.00,
            'subtotal' => 18000.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('products', ['id' => $productId]);
        $this->assertDatabaseHas('stock_mutations', ['product_id' => $productId]);
        $this->assertDatabaseHas('order_items', ['order_id' => $orderId]);

        // Test cascade on order delete -> order_items deleted
        DB::table('orders')->where('id', $orderId)->delete();
        $this->assertDatabaseMissing('orders', ['id' => $orderId]);
        $this->assertDatabaseMissing('order_items', ['order_id' => $orderId]);

        // Test cascade on category delete -> product & mutations deleted
        DB::table('categories')->where('id', $categoryId)->delete();
        $this->assertDatabaseMissing('categories', ['id' => $categoryId]);
        $this->assertDatabaseMissing('products', ['id' => $productId]);
        $this->assertDatabaseMissing('stock_mutations', ['product_id' => $productId]);
    }
}
