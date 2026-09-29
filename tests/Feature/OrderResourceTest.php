<?php

namespace Tests\Feature;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMutation;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderResourceTest extends TestCase
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
     * Test list orders page can be rendered.
     */
    public function test_can_render_order_list_page(): void
    {
        $order = Order::create([
            'order_number' => 'BL-20260929-0001',
            'total_amount' => 50000.00,
            'paid_amount' => 50000.00,
            'change_amount' => 0.00,
            'payment_method' => 'cash',
            'status' => 'paid',
            'ordered_at' => now(),
        ]);

        $this->actingAs($this->owner)
            ->get(OrderResource::getUrl('index'))
            ->assertSuccessful();

        Livewire::actingAs($this->owner)
            ->test(ListOrders::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$order]);
    }

    /**
     * Test manual creation and editing are disabled for OrderResource.
     */
    public function test_manual_create_and_edit_are_disabled(): void
    {
        $order = Order::create([
            'order_number' => 'BL-20260929-0002',
            'total_amount' => 30000.00,
            'paid_amount' => 50000.00,
            'change_amount' => 20000.00,
            'payment_method' => 'cash',
            'status' => 'paid',
            'ordered_at' => now(),
        ]);

        $this->assertFalse(OrderResource::canCreate());
        $this->assertFalse(OrderResource::canEdit($order));
        $this->assertFalse(OrderResource::canDelete($order));

        Livewire::actingAs($this->owner)
            ->test(ListOrders::class)
            ->assertActionDoesNotExist('create');
    }

    /**
     * Test filtering orders by payment method.
     */
    public function test_can_filter_orders_by_payment_method(): void
    {
        $cashOrder = Order::create([
            'order_number' => 'BL-20260929-CASH',
            'total_amount' => 25000.00,
            'paid_amount' => 30000.00,
            'change_amount' => 5000.00,
            'payment_method' => 'cash',
            'status' => 'paid',
            'ordered_at' => now(),
        ]);

        $qrisOrder = Order::create([
            'order_number' => 'BL-20260929-QRIS',
            'total_amount' => 45000.00,
            'paid_amount' => 45000.00,
            'change_amount' => 0.00,
            'payment_method' => 'qris',
            'status' => 'paid',
            'ordered_at' => now(),
        ]);

        Livewire::actingAs($this->owner)
            ->test(ListOrders::class)
            ->filterTable('payment_method', 'qris')
            ->assertCanSeeTableRecords([$qrisOrder])
            ->assertCanNotSeeTableRecords([$cashOrder]);
    }

    /**
     * Test filtering orders by status (paid vs cancelled).
     */
    public function test_can_filter_orders_by_status(): void
    {
        $paidOrder = Order::create([
            'order_number' => 'BL-20260929-PAID',
            'total_amount' => 20000.00,
            'paid_amount' => 20000.00,
            'change_amount' => 0.00,
            'payment_method' => 'cash',
            'status' => 'paid',
            'ordered_at' => now(),
        ]);

        $cancelledOrder = Order::create([
            'order_number' => 'BL-20260929-VOID',
            'total_amount' => 20000.00,
            'paid_amount' => 20000.00,
            'change_amount' => 0.00,
            'payment_method' => 'cash',
            'status' => 'cancelled',
            'ordered_at' => now(),
        ]);

        Livewire::actingAs($this->owner)
            ->test(ListOrders::class)
            ->filterTable('status', 'cancelled')
            ->assertCanSeeTableRecords([$cancelledOrder])
            ->assertCanNotSeeTableRecords([$paidOrder]);
    }

    /**
     * Test filtering orders by date range.
     */
    public function test_can_filter_orders_by_date_range(): void
    {
        $todayOrder = Order::create([
            'order_number' => 'BL-TODAY',
            'total_amount' => 35000.00,
            'paid_amount' => 35000.00,
            'change_amount' => 0.00,
            'payment_method' => 'cash',
            'status' => 'paid',
            'ordered_at' => now(),
        ]);

        $pastOrder = Order::create([
            'order_number' => 'BL-PAST',
            'total_amount' => 35000.00,
            'paid_amount' => 35000.00,
            'change_amount' => 0.00,
            'payment_method' => 'cash',
            'status' => 'paid',
            'ordered_at' => now()->subDays(5),
        ]);

        Livewire::actingAs($this->owner)
            ->test(ListOrders::class)
            ->filterTable('ordered_at', [
                'from' => now()->subDay()->toDateString(),
                'until' => now()->addDay()->toDateString(),
            ])
            ->assertCanSeeTableRecords([$todayOrder])
            ->assertCanNotSeeTableRecords([$pastOrder]);
    }

    /**
     * Test voiding a paid order restores product stock and logs mutations.
     */
    public function test_can_void_paid_order_and_restore_product_stocks(): void
    {
        $lele = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Pecel Lele',
            'cost_price' => 10000.00,
            'selling_price' => 18000.00,
            'stock' => 10,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        $bebek = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Bebek Goreng',
            'cost_price' => 18000.00,
            'selling_price' => 30000.00,
            'stock' => 15,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        $order = Order::create([
            'order_number' => 'BL-20260929-VOID-TEST',
            'total_amount' => 66000.00,
            'paid_amount' => 70000.00,
            'change_amount' => 4000.00,
            'payment_method' => 'cash',
            'status' => 'paid',
            'ordered_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $lele->id,
            'quantity' => 2,
            'cost_price' => 10000.00,
            'unit_price' => 18000.00,
            'subtotal' => 36000.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $bebek->id,
            'quantity' => 1,
            'cost_price' => 18000.00,
            'unit_price' => 30000.00,
            'subtotal' => 30000.00,
        ]);

        Livewire::actingAs($this->owner)
            ->test(ListOrders::class)
            ->callTableAction('voidOrder', $order, data: [
                'reason' => 'Salah input oleh kasir',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertEquals('cancelled', $order->fresh()->status);
        $this->assertEquals(12, $lele->fresh()->stock); // 10 + 2
        $this->assertEquals(16, $bebek->fresh()->stock); // 15 + 1

        $this->assertDatabaseHas('stock_mutations', [
            'product_id' => $lele->id,
            'type' => 'adjustment',
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('stock_mutations', [
            'product_id' => $bebek->id,
            'type' => 'adjustment',
            'quantity' => 1,
        ]);
    }

    /**
     * Test void action is hidden on already cancelled orders.
     */
    public function test_void_action_is_hidden_for_cancelled_orders(): void
    {
        $cancelledOrder = Order::create([
            'order_number' => 'BL-20260929-ALREADY-CANCELLED',
            'total_amount' => 18000.00,
            'paid_amount' => 20000.00,
            'change_amount' => 2000.00,
            'payment_method' => 'cash',
            'status' => 'cancelled',
            'ordered_at' => now(),
        ]);

        Livewire::actingAs($this->owner)
            ->test(ListOrders::class)
            ->assertTableActionHidden('voidOrder', $cancelledOrder);
    }

    /**
     * Test order detail and receipt reprint actions exist and render views accurately.
     */
    public function test_detail_and_reprint_actions_exist_and_render(): void
    {
        $order = Order::create([
            'order_number' => 'BL-20260929-MODAL-TEST',
            'total_amount' => 18000.00,
            'paid_amount' => 20000.00,
            'change_amount' => 2000.00,
            'payment_method' => 'cash',
            'status' => 'paid',
            'ordered_at' => now(),
        ]);

        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Pecel Lele',
            'cost_price' => 10000.00,
            'selling_price' => 18000.00,
            'stock' => 10,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'cost_price' => 10000.00,
            'unit_price' => 18000.00,
            'subtotal' => 18000.00,
        ]);

        Livewire::actingAs($this->owner)
            ->test(ListOrders::class)
            ->assertTableActionExists('detail', record: $order)
            ->assertTableActionExists('reprintReceipt', record: $order);

        // Test slideover order detail view rendering
        $detailHtml = view('filament.resources.orders.components.order-detail', [
            'order' => $order->load(['orderItems.product.category']),
        ])->render();

        $this->assertStringContainsString('BL-20260929-MODAL-TEST', $detailHtml);
        $this->assertStringContainsString('Pecel Lele', $detailHtml);
        $this->assertStringContainsString('Rp 10.000', $detailHtml);
        $this->assertStringContainsString('Rp 18.000', $detailHtml);

        // Test receipt modal view rendering
        $receiptHtml = view('filament.resources.orders.components.receipt-modal', [
            'order' => $order->load(['orderItems.product']),
        ])->render();

        $this->assertStringContainsString('BL-20260929-MODAL-TEST', $receiptHtml);
        $this->assertStringContainsString('BUDHE LAMONGAN', $receiptHtml);
    }
}
