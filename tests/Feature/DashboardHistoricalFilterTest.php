<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\HourlySalesChart;
use App\Filament\Widgets\StatsOverviewWidget;
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

class DashboardHistoricalFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Category $category;
    protected Product $lele;
    protected Product $bebek;

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

        $this->lele = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Pecel Lele',
            'cost_price' => 10000.00,
            'selling_price' => 18000.00,
            'stock' => 50,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        $this->bebek = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Bebek Goreng',
            'cost_price' => 18000.00,
            'selling_price' => 30000.00,
            'stock' => 50,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);
    }

    /**
     * Test Dashboard page renders successfully with filter schema.
     */
    public function test_dashboard_page_renders_filter_schema(): void
    {
        Livewire::actingAs($this->owner)
            ->test(Dashboard::class)
            ->assertSuccessful()
            ->assertSee('Filter Periode Operasional')
            ->assertSee('Dari Tanggal')
            ->assertSee('Sampai Tanggal');
    }

    /**
     * Test Dashboard automatically resets and scopes to today's data when no filter is applied.
     */
    public function test_dashboard_automatically_resets_and_scopes_to_today(): void
    {
        // 1. Transaction Yesterday (Rp 150.000)
        $yesterdayOrder = Order::create([
            'order_number' => 'BL-YEST-01',
            'total_amount' => 150000.00,
            'paid_amount' => 150000.00,
            'change_amount' => 0.00,
            'payment_method' => 'cash',
            'status' => 'paid',
            'ordered_at' => Carbon::yesterday()->setHour(14),
        ]);
        OrderItem::create([
            'order_id' => $yesterdayOrder->id,
            'product_id' => $this->bebek->id,
            'quantity' => 5,
            'cost_price' => 18000.00,
            'unit_price' => 30000.00,
            'subtotal' => 150000.00,
        ]);

        // 2. Transaction Today (Rp 36.000)
        $todayOrder = Order::create([
            'order_number' => 'BL-TODAY-01',
            'total_amount' => 36000.00,
            'paid_amount' => 50000.00,
            'change_amount' => 14000.00,
            'payment_method' => 'cash',
            'status' => 'paid',
            'ordered_at' => Carbon::today()->setHour(11),
        ]);
        OrderItem::create([
            'order_id' => $todayOrder->id,
            'product_id' => $this->lele->id,
            'quantity' => 2,
            'cost_price' => 10000.00,
            'unit_price' => 18000.00,
            'subtotal' => 36000.00,
        ]);

        // Without filter (Default Daily Reset): Only Today's 36.000 should be visible in stats
        Livewire::actingAs($this->owner)
            ->test(StatsOverviewWidget::class)
            ->assertSuccessful()
            ->assertSee('Total Omset Hari Ini')
            ->assertSee('Rp 36.000')
            ->assertSee('Uang Kas di Laci')
            ->assertDontSee('Rp 150.000');
    }

    /**
     * Test Owner can view historical data for previous operational days via date filter.
     */
    public function test_owner_can_view_historical_data_via_date_filter(): void
    {
        $pastDate = Carbon::today()->subDays(5);

        // Transaction 5 days ago (Rp 90.000, 3x Bebek)
        $pastOrder = Order::create([
            'order_number' => 'BL-PAST-05',
            'total_amount' => 90000.00,
            'paid_amount' => 90000.00,
            'change_amount' => 0.00,
            'payment_method' => 'qris',
            'status' => 'paid',
            'ordered_at' => $pastDate->copy()->setHour(13)->setMinute(30),
        ]);
        OrderItem::create([
            'order_id' => $pastOrder->id,
            'product_id' => $this->bebek->id,
            'quantity' => 3,
            'cost_price' => 18000.00,
            'unit_price' => 30000.00,
            'subtotal' => 90000.00,
        ]);

        $filterPayload = [
            'startDate' => $pastDate->format('Y-m-d'),
            'endDate' => $pastDate->format('Y-m-d'),
        ];

        // 1. Verify StatsOverviewWidget updates to historical metrics
        Livewire::actingAs($this->owner)
            ->test(StatsOverviewWidget::class, ['pageFilters' => $filterPayload])
            ->assertSuccessful()
            ->assertSee('Total Omset')
            ->assertSee('Rp 90.000')
            ->assertSee('Uang Masuk Non-Tunai')
            ->assertSee('1 transaksi QRIS & Transfer');

        // 2. Verify TopSellingProductsWidget shows ranking for that historical date
        Livewire::actingAs($this->owner)
            ->test(TopSellingProductsWidget::class, ['pageFilters' => $filterPayload])
            ->assertSuccessful()
            ->assertSee('Bebek Goreng')
            ->assertSee('3 porsi')
            ->assertSee('90.000');

        // 3. Verify HourlySalesChart maps the 13:00 peak sale
        $chartTest = Livewire::actingAs($this->owner)
            ->test(HourlySalesChart::class, ['pageFilters' => $filterPayload])
            ->assertSuccessful();

        /** @var HourlySalesChart $chartInstance */
        $chartInstance = $chartTest->instance();
        $reflection = new \ReflectionMethod($chartInstance, 'getData');
        $reflection->setAccessible(true);
        $chartData = $reflection->invoke($chartInstance);

        // Index 4 corresponds to 13:00 (09:00 -> 0, 10:00 -> 1, 11:00 -> 2, 12:00 -> 3, 13:00 -> 4)
        $this->assertEquals(90000.0, $chartData['datasets'][0]['data'][4]);
    }
}
