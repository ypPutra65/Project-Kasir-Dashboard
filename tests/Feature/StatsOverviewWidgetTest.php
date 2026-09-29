<?php

namespace Tests\Feature;

use App\Filament\Widgets\StatsOverviewWidget;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class StatsOverviewWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Product $lele;
    protected Product $bebek;
    protected Product $esTeh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UserSeeder::class);
        $this->owner = User::where('email', 'owner@budhelamongan.local')->first();

        $category = Category::create([
            'name' => 'Makanan Utama',
            'slug' => 'makanan-utama',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->lele = Product::create([
            'category_id' => $category->id,
            'name' => 'Pecel Lele',
            'cost_price' => 10000.00,
            'selling_price' => 18000.00,
            'stock' => 20,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        $this->bebek = Product::create([
            'category_id' => $category->id,
            'name' => 'Bebek Goreng',
            'cost_price' => 18000.00,
            'selling_price' => 30000.00,
            'stock' => 20,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        $this->esTeh = Product::create([
            'category_id' => $category->id,
            'name' => 'Es Teh Manis',
            'cost_price' => 1500.00,
            'selling_price' => 5000.00,
            'stock' => 50,
            'min_stock_alert' => 10,
            'is_active' => true,
        ]);
    }

    /**
     * Test StatsOverviewWidget renders correctly with accurate financial calculations.
     */
    public function test_stats_overview_widget_calculates_daily_metrics_accurately(): void
    {
        // 1. Today Order 1: Cash (Lele 1x: 18.000, HPP: 10.000)
        $order1 = Order::create([
            'order_number' => 'BL-20260929-0001',
            'total_amount' => 18000.00,
            'paid_amount' => 20000.00,
            'change_amount' => 2000.00,
            'payment_method' => 'cash',
            'status' => 'paid',
            'ordered_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $this->lele->id,
            'quantity' => 1,
            'cost_price' => 10000.00,
            'unit_price' => 18000.00,
            'subtotal' => 18000.00,
        ]);

        // 2. Today Order 2: QRIS (Bebek 1x: 30.000, HPP: 18.000)
        $order2 = Order::create([
            'order_number' => 'BL-20260929-0002',
            'total_amount' => 30000.00,
            'paid_amount' => 30000.00,
            'change_amount' => 0.00,
            'payment_method' => 'qris',
            'status' => 'paid',
            'ordered_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => $this->bebek->id,
            'quantity' => 1,
            'cost_price' => 18000.00,
            'unit_price' => 30000.00,
            'subtotal' => 30000.00,
        ]);

        // 3. Today Order 3: Cancelled / Void (Should NOT count in revenue/profit)
        $order3 = Order::create([
            'order_number' => 'BL-20260929-0003',
            'total_amount' => 5000.00,
            'paid_amount' => 5000.00,
            'change_amount' => 0.00,
            'payment_method' => 'cash',
            'status' => 'cancelled',
            'ordered_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $order3->id,
            'product_id' => $this->esTeh->id,
            'quantity' => 1,
            'cost_price' => 1500.00,
            'unit_price' => 5000.00,
            'subtotal' => 5000.00,
        ]);

        // 4. Yesterday Order (For comparison)
        $orderYesterday = Order::create([
            'order_number' => 'BL-20260928-0001',
            'total_amount' => 18000.00,
            'paid_amount' => 20000.00,
            'change_amount' => 2000.00,
            'payment_method' => 'cash',
            'status' => 'paid',
            'ordered_at' => Carbon::yesterday(),
        ]);
        OrderItem::create([
            'order_id' => $orderYesterday->id,
            'product_id' => $this->lele->id,
            'quantity' => 1,
            'cost_price' => 10000.00,
            'unit_price' => 18000.00,
            'subtotal' => 18000.00,
        ]);

        // Expected Calculations:
        // Today Total Revenue (Paid): 18.000 + 30.000 = 48.000
        // Today Cash Drawer: 18.000
        // Today Non-Cash (QRIS): 30.000
        // Today Total HPP: 10.000 + 18.000 = 28.000
        // Today Gross Profit: 48.000 - 28.000 = 20.000

        Livewire::actingAs($this->owner)
            ->test(StatsOverviewWidget::class)
            ->assertSuccessful()
            ->assertSee('Total Omset Hari Ini')
            ->assertSee('Rp 48.000')
            ->assertSee('Uang Kas di Laci')
            ->assertSee('Rp 18.000')
            ->assertSee('Uang Masuk Non-Tunai')
            ->assertSee('Rp 30.000')
            ->assertSee('Estimasi Laba Kotor')
            ->assertSee('Rp 20.000');
    }
}
