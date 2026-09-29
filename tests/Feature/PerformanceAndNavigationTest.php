<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\PosPage;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PerformanceAndNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UserSeeder::class);
        $this->owner = User::where('email', 'owner@budhelamongan.local')->first();

        $this->category = Category::create([
            'name' => 'Makanan Utama',
            'slug' => 'makanan-utama',
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    /**
     * Test sidebar navigation items are ordered correctly (Pos 1 -> 5).
     */
    public function test_sidebar_navigation_order_and_labels(): void
    {
        $this->assertEquals(1, PosPage::getNavigationSort());
        $this->assertEquals('Kasir (POS)', PosPage::getNavigationLabel());

        $this->assertEquals(2, Dashboard::getNavigationSort());
        $this->assertEquals('Dashboard Monitoring', Dashboard::getNavigationLabel());

        $this->assertEquals(3, OrderResource::getNavigationSort());
        $this->assertEquals('Riwayat Transaksi', OrderResource::getNavigationLabel());

        $this->assertEquals(4, ProductResource::getNavigationSort());
        $this->assertEquals('Menu Makanan & Minuman', ProductResource::getNavigationLabel());

        $this->assertEquals(5, CategoryResource::getNavigationSort());
        $this->assertEquals('Kategori Menu', CategoryResource::getNavigationLabel());
    }

    /**
     * Test POS page contains F2 and F9 keyboard shortcut attributes and scripts.
     */
    public function test_pos_page_contains_f2_and_f9_hotkeys(): void
    {
        Livewire::actingAs($this->owner)
            ->test(PosPage::class)
            ->assertSuccessful()
            ->assertSee('pos-search-input')
            ->assertSee('F2')
            ->assertSee('F9');
    }

    /**
     * Test eager loading on OrderResource table eliminates N+1 queries.
     */
    public function test_order_resource_eager_loads_relations(): void
    {
        $p1 = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Pecel Lele',
            'cost_price' => 10000,
            'selling_price' => 18000,
            'stock' => 20,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        $p2 = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Bebek Goreng',
            'cost_price' => 18000,
            'selling_price' => 30000,
            'stock' => 20,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $order = Order::create([
                'order_number' => "BL-TEST-EAGER-{$i}",
                'total_amount' => 48000,
                'paid_amount' => 50000,
                'change_amount' => 2000,
                'payment_method' => 'cash',
                'status' => 'paid',
                'ordered_at' => now(),
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $p1->id,
                'quantity' => 1,
                'cost_price' => 10000,
                'unit_price' => 18000,
                'subtotal' => 18000,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $p2->id,
                'quantity' => 1,
                'cost_price' => 18000,
                'unit_price' => 30000,
                'subtotal' => 30000,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::actingAs($this->owner)
            ->test(ListOrders::class)
            ->assertSuccessful();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Ensure total query count is reasonable (constant O(1) queries, not O(N) per order record)
        $this->assertLessThan(20, count($queries));
    }

    /**
     * Test eager loading on ProductResource table eliminates N+1 queries.
     */
    public function test_product_resource_eager_loads_category_relation(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Product::create([
                'category_id' => $this->category->id,
                'name' => "Menu Test {$i}",
                'cost_price' => 10000,
                'selling_price' => 18000,
                'stock' => 20,
                'min_stock_alert' => 5,
                'is_active' => true,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::actingAs($this->owner)
            ->test(ListProducts::class)
            ->assertSuccessful();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThan(15, count($queries));
    }
}
