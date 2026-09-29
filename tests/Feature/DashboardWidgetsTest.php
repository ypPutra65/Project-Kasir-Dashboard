<?php

namespace Tests\Feature;

use App\Filament\Widgets\HourlySalesChart;
use App\Filament\Widgets\TopSellingProductsWidget;
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

class DashboardWidgetsTest extends TestCase
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
     * Test HourlySalesChart renders successfully and accurately maps 09:00-23:00 hourly sales.
     */
    public function test_hourly_sales_chart_renders_and_aggregates_hourly_data(): void
    {
        // 1. Order at 10:30 (Rp 50.000)
        Order::create([
            'order_number' => 'BL-1030',
            'total_amount' => 50000.00,
            'paid_amount' => 50000.00,
            'change_amount' => 0.00,
            'payment_method' => 'cash',
            'status' => 'paid',
            'ordered_at' => Carbon::today()->setHour(10)->setMinute(30),
        ]);

        // 2. Order at 12:15 (Rp 75.000)
        Order::create([
            'order_number' => 'BL-1215',
            'total_amount' => 75000.00,
            'paid_amount' => 75000.00,
            'change_amount' => 0.00,
            'payment_method' => 'qris',
            'status' => 'paid',
            'ordered_at' => Carbon::today()->setHour(12)->setMinute(15),
        ]);

        // 3. Order at 19:45 (Rp 120.000)
        Order::create([
            'order_number' => 'BL-1945',
            'total_amount' => 120000.00,
            'paid_amount' => 120000.00,
            'change_amount' => 0.00,
            'payment_method' => 'cash',
            'status' => 'paid',
            'ordered_at' => Carbon::today()->setHour(19)->setMinute(45),
        ]);

        // 4. Cancelled order at 12:00 (Rp 100.000) - Should NOT be included
        Order::create([
            'order_number' => 'BL-1200-VOID',
            'total_amount' => 100000.00,
            'paid_amount' => 100000.00,
            'change_amount' => 0.00,
            'payment_method' => 'cash',
            'status' => 'cancelled',
            'ordered_at' => Carbon::today()->setHour(12)->setMinute(0),
        ]);

        // 5. Yesterday order at 12:00 (Rp 80.000) - Should NOT be included
        Order::create([
            'order_number' => 'BL-YESTERDAY',
            'total_amount' => 80000.00,
            'paid_amount' => 80000.00,
            'change_amount' => 0.00,
            'payment_method' => 'cash',
            'status' => 'paid',
            'ordered_at' => Carbon::yesterday()->setHour(12)->setMinute(0),
        ]);

        $test = Livewire::actingAs($this->owner)
            ->test(HourlySalesChart::class)
            ->assertSuccessful();

        /** @var HourlySalesChart $widgetInstance */
        $widgetInstance = $test->instance();
        $reflection = new \ReflectionMethod($widgetInstance, 'getData');
        $reflection->setAccessible(true);
        $data = $reflection->invoke($widgetInstance);

        $this->assertArrayHasKey('labels', $data);
        $this->assertArrayHasKey('datasets', $data);
        $this->assertCount(15, $data['labels']); // 09:00 to 23:00 = 15 hours
        $this->assertEquals('09:00', $data['labels'][0]);
        $this->assertEquals('23:00', $data['labels'][14]);

        $datasetValues = $data['datasets'][0]['data'];
        // Index 1 corresponds to 10:00 (50.000)
        $this->assertEquals(50000.0, $datasetValues[1]);
        // Index 3 corresponds to 12:00 (75.000)
        $this->assertEquals(75000.0, $datasetValues[3]);
        // Index 10 corresponds to 19:00 (120.000)
        $this->assertEquals(120000.0, $datasetValues[10]);
        // Index 0 corresponds to 09:00 (0)
        $this->assertEquals(0.0, $datasetValues[0]);
    }

    /**
     * Test TopSellingProductsWidget lists top 5 products by quantity sold today.
     */
    public function test_top_selling_products_widget_renders_top_5_menus(): void
    {
        $products = [];
        $menuNames = [
            'Pecel Lele' => 18000,
            'Bebek Goreng' => 30000,
            'Es Teh Manis' => 5000,
            'Ayam Penyet' => 22000,
            'Es Jeruk' => 7000,
            'Tahu Tempe Goreng' => 6000, // Rank 6, should be excluded by limit 5
        ];

        foreach ($menuNames as $name => $price) {
            $products[$name] = Product::create([
                'category_id' => $this->category->id,
                'name' => $name,
                'cost_price' => $price * 0.6,
                'selling_price' => $price,
                'stock' => 50,
                'min_stock_alert' => 5,
                'is_active' => true,
            ]);
        }

        $order = Order::create([
            'order_number' => 'BL-TOP-SALES',
            'total_amount' => 500000.00,
            'paid_amount' => 500000.00,
            'change_amount' => 0.00,
            'payment_method' => 'cash',
            'status' => 'paid',
            'ordered_at' => now(),
        ]);

        // Quantities:
        // Es Teh: 25 porsi (Rank 1)
        // Bebek Goreng: 20 porsi (Rank 2)
        // Pecel Lele: 15 porsi (Rank 3)
        // Ayam Penyet: 10 porsi (Rank 4)
        // Es Jeruk: 5 porsi (Rank 5)
        // Tahu Tempe: 2 porsi (Rank 6)
        $salesQuantities = [
            'Es Teh Manis' => 25,
            'Bebek Goreng' => 20,
            'Pecel Lele' => 15,
            'Ayam Penyet' => 10,
            'Es Jeruk' => 5,
            'Tahu Tempe Goreng' => 2,
        ];

        foreach ($salesQuantities as $name => $qty) {
            $prod = $products[$name];
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $prod->id,
                'quantity' => $qty,
                'cost_price' => $prod->cost_price,
                'unit_price' => $prod->selling_price,
                'subtotal' => $prod->selling_price * $qty,
            ]);
        }

        Livewire::actingAs($this->owner)
            ->test(TopSellingProductsWidget::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([
                $products['Es Teh Manis'],
                $products['Bebek Goreng'],
                $products['Pecel Lele'],
                $products['Ayam Penyet'],
                $products['Es Jeruk'],
            ])
            ->assertCanNotSeeTableRecords([
                $products['Tahu Tempe Goreng'],
            ]);
    }

    /**
     * Test TopSellingProductsWidget empty state when no sales exist today.
     */
    public function test_top_selling_products_widget_empty_state(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TopSellingProductsWidget::class)
            ->assertSuccessful()
            ->assertSee('Belum Ada Penjualan Hari Ini');
    }
}
