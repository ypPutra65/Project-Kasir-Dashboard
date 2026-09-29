<?php

namespace Tests\Feature;

use App\Filament\Pages\PosPage;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Widgets\HourlySalesChart;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\TopSellingProductsWidget;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMutation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class PosEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Warung Buka: Seeding database kasir_db
        $this->seed(DatabaseSeeder::class);
        $this->owner = User::where('email', 'owner@budhelamongan.local')->firstOrFail();
    }

    /**
     * Comprehensive End-to-End Test: Siklus Operasional Harian Warung Budhe Lamongan
     * (Buka Warung -> Belanja Pagi -> Transaksi Kasir -> Void Nota -> Tutup Kasir / Dashboard).
     */
    public function test_full_daily_operational_cycle_from_open_to_close(): void
    {
        // =========================================================================
        // SKENARIO 1: Warung Buka, Cek Ketersediaan Stok Awal dari Seeder
        // =========================================================================
        $lele = Product::where('name', 'Pecel Lele')->firstOrFail();
        $bebek = Product::where('name', 'Bebek Goreng')->firstOrFail();
        $ayam = Product::where('name', 'Ayam Penyet')->firstOrFail();
        $esTeh = Product::where('name', 'Es Teh Manis')->firstOrFail();
        $esJeruk = Product::where('name', 'Es Jeruk')->firstOrFail();

        $this->assertEquals(30, $lele->stock);
        $this->assertEquals(25, $bebek->stock);
        $this->assertEquals(25, $ayam->stock);
        $this->assertEquals(50, $esTeh->stock);
        $this->assertEquals(40, $esJeruk->stock);

        // =========================================================================
        // SKENARIO 2: Owner Menambah Stok Pagi via Mutasi Stok ('in')
        // =========================================================================
        // Tambah Bebek Goreng +10 porsi (25 -> 35)
        Livewire::actingAs($this->owner)
            ->test(ListProducts::class)
            ->callTableAction('adjustStock', $bebek, data: [
                'type' => 'in',
                'quantity' => 10,
                'notes' => 'Belanja bebek segar pasar pagi',
            ])
            ->assertHasNoTableActionErrors();

        // Tambah Pecel Lele +20 porsi (30 -> 50)
        Livewire::actingAs($this->owner)
            ->test(ListProducts::class)
            ->callTableAction('adjustStock', $lele, data: [
                'type' => 'in',
                'quantity' => 20,
                'notes' => 'Belanja lele segar pasar pagi',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertEquals(35, $bebek->fresh()->stock);
        $this->assertEquals(50, $lele->fresh()->stock);

        // =========================================================================
        // SKENARIO 3: Transaksi 1 (Tunai dengan Uang Pas)
        // 2x Pecel Lele (@18.000 = 36.000, HPP: 2x 10.000 = 20.000)
        // 2x Es Teh Manis (@5.000 = 10.000, HPP: 2x 1.500 = 3.000)
        // Total: Rp 46.000, Bayar: Rp 46.000 (Pas), Kembalian: Rp 0
        // =========================================================================
        Livewire::actingAs($this->owner)
            ->test(PosPage::class)
            ->call('addToCart', $lele->id)
            ->call('incrementQty', $lele->id)
            ->call('addToCart', $esTeh->id)
            ->call('incrementQty', $esTeh->id)
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', 46000)
            ->call('checkout')
            ->assertHasNoErrors()
            ->assertSet('showSuccessModal', true);

        $order1 = Order::where('total_amount', 46000)->firstOrFail();
        $this->assertEquals('paid', $order1->status);
        $this->assertEquals('cash', $order1->payment_method);
        $this->assertEquals(46000.0, (float) $order1->paid_amount);
        $this->assertEquals(0.0, (float) $order1->change_amount);
        $this->assertCount(2, $order1->orderItems);

        // Verifikasi stok terpotong
        $this->assertEquals(48, $lele->fresh()->stock); // 50 - 2
        $this->assertEquals(48, $esTeh->fresh()->stock); // 50 - 2

        // =========================================================================
        // SKENARIO 4: Transaksi 2 (Tunai dengan Pecahan Lebih Besar Rp 50.000)
        // 1x Bebek Goreng (@30.000 = 30.000, HPP: 18.000)
        // 1x Es Jeruk (@7.000 = 7.000, HPP: 2.500)
        // Total: Rp 37.000, Bayar: Rp 50.000, Kembalian: Rp 13.000
        // =========================================================================
        Livewire::actingAs($this->owner)
            ->test(PosPage::class)
            ->call('addToCart', $bebek->id)
            ->call('addToCart', $esJeruk->id)
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', 50000)
            ->call('checkout')
            ->assertHasNoErrors()
            ->assertSet('showSuccessModal', true);

        $order2 = Order::where('total_amount', 37000)->firstOrFail();
        $this->assertEquals('paid', $order2->status);
        $this->assertEquals('cash', $order2->payment_method);
        $this->assertEquals(50000.0, (float) $order2->paid_amount);
        $this->assertEquals(13000.0, (float) $order2->change_amount);

        // Verifikasi stok terpotong
        $this->assertEquals(34, $bebek->fresh()->stock); // 35 - 1
        $this->assertEquals(39, $esJeruk->fresh()->stock); // 40 - 1

        // =========================================================================
        // SKENARIO 5: Transaksi 3 (Pembayaran Non-Tunai QRIS)
        // 2x Ayam Penyet (@22.000 = 44.000, HPP: 2x 13.000 = 26.000)
        // 2x Es Teh Manis (@5.000 = 10.000, HPP: 2x 1.500 = 3.000)
        // Total: Rp 54.000, Bayar: Rp 54.000 (QRIS)
        // =========================================================================
        Livewire::actingAs($this->owner)
            ->test(PosPage::class)
            ->call('addToCart', $ayam->id)
            ->call('incrementQty', $ayam->id)
            ->call('addToCart', $esTeh->id)
            ->call('incrementQty', $esTeh->id)
            ->set('paymentMethod', 'qris')
            ->set('paidAmount', 54000)
            ->call('checkout')
            ->assertHasNoErrors()
            ->assertSet('showSuccessModal', true);

        $order3 = Order::where('total_amount', 54000)->firstOrFail();
        $this->assertEquals('paid', $order3->status);
        $this->assertEquals('qris', $order3->payment_method);
        $this->assertEquals(54000.0, (float) $order3->paid_amount);
        $this->assertEquals(0.0, (float) $order3->change_amount);

        // Verifikasi stok terpotong
        $this->assertEquals(23, $ayam->fresh()->stock); // 25 - 2
        $this->assertEquals(46, $esTeh->fresh()->stock); // 48 - 2

        // =========================================================================
        // SKENARIO 6: Pembatalan Transaksi (Void) pada Nota 2
        // Batalkan Order 2 (Bebek Goreng 1x, Es Jeruk 1x)
        // Stok Bebek Goreng harus kembali ke 35 (34 + 1)
        // Stok Es Jeruk harus kembali ke 40 (39 + 1)
        // =========================================================================
        Livewire::actingAs($this->owner)
            ->test(ListOrders::class)
            ->callTableAction('voidOrder', $order2, data: [
                'reason' => 'Pelanggan membatalkan pesanan karena mendadak pergi',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertEquals('cancelled', $order2->fresh()->status);
        $this->assertEquals(35, $bebek->fresh()->stock);
        $this->assertEquals(40, $esJeruk->fresh()->stock);

        $this->assertDatabaseHas('stock_mutations', [
            'product_id' => $bebek->id,
            'type' => 'adjustment',
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('stock_mutations', [
            'product_id' => $esJeruk->id,
            'type' => 'adjustment',
            'quantity' => 1,
        ]);

        // =========================================================================
        // SKENARIO 7: Validasi Metrik Akhir Dashboard Monitoring
        // Order Aktif (Paid): Order 1 (Rp 46.000 Cash) + Order 3 (Rp 54.000 QRIS)
        // - Total Omset: Rp 100.000
        // - Kas di Laci: Rp 46.000
        // - Non-Tunai: Rp 54.000
        // - Total HPP: (20.000 + 3.000) + (26.000 + 3.000) = Rp 52.000
        // - Laba Kotor: 100.000 - 52.000 = Rp 48.000 (Margin 48%)
        // =========================================================================
        Livewire::actingAs($this->owner)
            ->test(StatsOverviewWidget::class)
            ->assertSuccessful()
            ->assertSee('Rp 100.000') // Total Omset Hari Ini
            ->assertSee('Rp 46.000')  // Uang Kas di Laci
            ->assertSee('Rp 54.000')  // Uang Masuk Non-Tunai
            ->assertSee('Rp 48.000')  // Estimasi Laba Kotor
            ->assertSee('48%');       // Margin Keuntungan

        // Validasi Top 5 Menu Terlaris Widget (Es Teh: 4 porsi, Lele: 2 porsi, Ayam: 2 porsi)
        Livewire::actingAs($this->owner)
            ->test(TopSellingProductsWidget::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$esTeh, $lele, $ayam])
            ->assertCanNotSeeTableRecords([$bebek, $esJeruk]); // Bebek & Es Jeruk transaksi dibatalkan

        // Validasi Grafik Penjualan Per Jam
        $chartTest = Livewire::actingAs($this->owner)
            ->test(HourlySalesChart::class)
            ->assertSuccessful();

        /** @var HourlySalesChart $chartInstance */
        $chartInstance = $chartTest->instance();
        $reflection = new \ReflectionMethod($chartInstance, 'getData');
        $reflection->setAccessible(true);
        $chartData = $reflection->invoke($chartInstance);

        $totalChartRevenue = array_sum($chartData['datasets'][0]['data']);
        $this->assertEquals(100000.0, (float) $totalChartRevenue);
    }
}
