<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMutation;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentModelsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Category model relationships and properties.
     */
    public function test_category_model_relationships(): void
    {
        $category = Category::create([
            'name' => 'Makanan Utama',
            'slug' => 'makanan-utama',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Pecel Lele',
            'cost_price' => 10000.00,
            'selling_price' => 18000.00,
            'stock' => 20,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        $this->assertTrue($category->products->contains($product));
        $this->assertEquals(1, $category->products()->count());
        $this->assertTrue($category->is_active);
    }

    /**
     * Test Product model relationships, accessors, and helper methods.
     */
    public function test_product_model_functionality(): void
    {
        $category = Category::create([
            'name' => 'Makanan Utama',
            'slug' => 'makanan-utama',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Bebek Goreng',
            'cost_price' => 18000.00,
            'selling_price' => 30000.00,
            'stock' => 5,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        // Category relationship
        $this->assertEquals($category->id, $product->category->id);
        $this->assertEquals('Makanan Utama', $product->category->name);

        // Low stock helper
        $this->assertTrue($product->isLowStock());

        $product->update(['stock' => 10]);
        $this->assertFalse($product->isLowStock());

        // Price formatting accessors
        $this->assertEquals('Rp 30.000', $product->formatted_selling_price);
        $this->assertEquals('Rp 18.000', $product->formatted_cost_price);
    }

    /**
     * Test StockMutation model relationship and casts.
     */
    public function test_stock_mutation_relationship(): void
    {
        $category = Category::create([
            'name' => 'Minuman',
            'slug' => 'minuman',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Es Teh Manis',
            'cost_price' => 1500.00,
            'selling_price' => 5000.00,
            'stock' => 50,
            'min_stock_alert' => 10,
        ]);

        $mutation = StockMutation::create([
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 50,
            'notes' => 'Restock pagi',
            'created_at' => now(),
        ]);

        $this->assertEquals($product->id, $mutation->product->id);
        $this->assertTrue($product->stockMutations->contains($mutation));
    }

    /**
     * Test Order model relationships, scopes, and accessors.
     */
    public function test_order_model_functionality(): void
    {
        $category = Category::create([
            'name' => 'Makanan Utama',
            'slug' => 'makanan-utama',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Ayam Penyet',
            'cost_price' => 13000.00,
            'selling_price' => 22000.00,
            'stock' => 20,
        ]);

        $todayOrder = Order::create([
            'order_number' => 'BL-20260929-0001',
            'total_amount' => 22000.00,
            'paid_amount' => 50000.00,
            'change_amount' => 28000.00,
            'payment_method' => 'cash',
            'status' => 'paid',
            'ordered_at' => now(),
        ]);

        $yesterdayOrder = Order::create([
            'order_number' => 'BL-20260928-0001',
            'total_amount' => 22000.00,
            'paid_amount' => 22000.00,
            'change_amount' => 0.00,
            'payment_method' => 'qris',
            'status' => 'cancelled',
            'ordered_at' => now()->subDay(),
        ]);

        $orderItem = OrderItem::create([
            'order_id' => $todayOrder->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'cost_price' => 13000.00,
            'unit_price' => 22000.00,
            'subtotal' => 22000.00,
        ]);

        // Relationships
        $this->assertTrue($todayOrder->orderItems->contains($orderItem));
        $this->assertEquals($todayOrder->id, $orderItem->order->id);
        $this->assertEquals($product->id, $orderItem->product->id);

        // Accessors
        $this->assertEquals('Rp 22.000', $todayOrder->formatted_total_amount);
        $this->assertEquals('Rp 22.000', $orderItem->formatted_subtotal);

        // Query Scopes
        $todayOrders = Order::today()->get();
        $this->assertTrue($todayOrders->contains($todayOrder));
        $this->assertFalse($todayOrders->contains($yesterdayOrder));

        $paidOrders = Order::paid()->get();
        $this->assertTrue($paidOrders->contains($todayOrder));
        $this->assertFalse($paidOrders->contains($yesterdayOrder));
    }

    /**
     * Test Lamongan seeders populate menu data properly.
     */
    public function test_lamongan_seeders_populate_menu(): void
    {
        $this->seed([
            CategorySeeder::class,
            ProductSeeder::class,
        ]);

        $this->assertDatabaseHas('categories', ['slug' => 'makanan-utama']);
        $this->assertDatabaseHas('categories', ['slug' => 'minuman']);
        $this->assertDatabaseHas('categories', ['slug' => 'sambal-ekstra']);

        $this->assertDatabaseHas('products', ['name' => 'Pecel Lele', 'selling_price' => 18000.00]);
        $this->assertDatabaseHas('products', ['name' => 'Bebek Goreng', 'selling_price' => 30000.00]);
        $this->assertDatabaseHas('products', ['name' => 'Ayam Penyet', 'selling_price' => 22000.00]);
        $this->assertDatabaseHas('products', ['name' => 'Es Teh Manis', 'selling_price' => 5000.00]);
        $this->assertDatabaseHas('products', ['name' => 'Es Jeruk', 'selling_price' => 7000.00]);

        $this->assertEquals(3, Category::count());
        $this->assertEquals(5, Product::count());
        $this->assertEquals(5, StockMutation::where('type', 'in')->count());
    }
}
