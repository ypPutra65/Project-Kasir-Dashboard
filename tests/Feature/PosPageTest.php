<?php

namespace Tests\Feature;

use App\Filament\Pages\PosPage;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Category $foodCategory;
    protected Category $drinkCategory;
    protected Product $lele;
    protected Product $esTeh;
    protected Product $outOfStockProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UserSeeder::class);
        $this->owner = User::where('email', 'owner@budhelamongan.local')->first();

        $this->foodCategory = Category::create([
            'name' => 'Makanan Utama',
            'slug' => 'makanan-utama',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->drinkCategory = Category::create([
            'name' => 'Minuman',
            'slug' => 'minuman',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $this->lele = Product::create([
            'category_id' => $this->foodCategory->id,
            'name' => 'Pecel Lele Goreng',
            'cost_price' => 10000.00,
            'selling_price' => 18000.00,
            'stock' => 5,
            'min_stock_alert' => 2,
            'is_active' => true,
        ]);

        $this->esTeh = Product::create([
            'category_id' => $this->drinkCategory->id,
            'name' => 'Es Teh Manis',
            'cost_price' => 1500.00,
            'selling_price' => 5000.00,
            'stock' => 20,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        $this->outOfStockProduct = Product::create([
            'category_id' => $this->foodCategory->id,
            'name' => 'Bebek Bakar',
            'cost_price' => 18000.00,
            'selling_price' => 30000.00,
            'stock' => 0,
            'min_stock_alert' => 2,
            'is_active' => true,
        ]);
    }

    /**
     * Test POS page can be rendered for authenticated owner.
     */
    public function test_can_render_pos_page(): void
    {
        $this->actingAs($this->owner)
            ->get(PosPage::getUrl())
            ->assertSuccessful();

        Livewire::actingAs($this->owner)
            ->test(PosPage::class)
            ->assertSuccessful()
            ->assertSee('Kasir (Point of Sale)')
            ->assertSee('Ringkasan Pesanan')
            ->assertSee('Pecel Lele Goreng')
            ->assertSee('Es Teh Manis');
    }

    /**
     * Test category filter on POS page.
     */
    public function test_can_filter_pos_menu_by_category(): void
    {
        Livewire::actingAs($this->owner)
            ->test(PosPage::class)
            ->call('selectCategory', $this->drinkCategory->id)
            ->assertSet('selectedCategoryId', $this->drinkCategory->id)
            ->assertSee('Es Teh Manis')
            ->assertDontSee('Pecel Lele Goreng');
    }

    /**
     * Test search filter on POS page.
     */
    public function test_can_search_pos_menu(): void
    {
        Livewire::actingAs($this->owner)
            ->test(PosPage::class)
            ->set('search', 'Lele')
            ->assertSee('Pecel Lele Goreng')
            ->assertDontSee('Es Teh Manis');
    }

    /**
     * Test adding product to cart and computing total amount.
     */
    public function test_can_add_item_to_cart_and_calculate_totals(): void
    {
        Livewire::actingAs($this->owner)
            ->test(PosPage::class)
            ->call('addToCart', $this->lele->id)
            ->assertSet("cart.{$this->lele->id}.qty", 1)
            ->assertSet("cart.{$this->lele->id}.price", 18000.00)
            ->assertSet("cart.{$this->lele->id}.subtotal", 18000.00)
            ->call('addToCart', $this->esTeh->id)
            ->assertSet("cart.{$this->esTeh->id}.qty", 1)
            ->assertSet("cart.{$this->esTeh->id}.subtotal", 5000.00);
    }

    /**
     * Test incrementing and decrementing quantity in cart.
     */
    public function test_can_increment_and_decrement_cart_quantity(): void
    {
        Livewire::actingAs($this->owner)
            ->test(PosPage::class)
            ->call('addToCart', $this->lele->id)
            ->call('incrementQty', $this->lele->id)
            ->assertSet("cart.{$this->lele->id}.qty", 2)
            ->assertSet("cart.{$this->lele->id}.subtotal", 36000.00)
            ->call('decrementQty', $this->lele->id)
            ->assertSet("cart.{$this->lele->id}.qty", 1)
            ->assertSet("cart.{$this->lele->id}.subtotal", 18000.00)
            ->call('decrementQty', $this->lele->id)
            ->assertCount('cart', 0);
    }

    /**
     * Test manual text input for changing cart quantity.
     */
    public function test_can_manually_update_product_quantity_in_cart(): void
    {
        Livewire::actingAs($this->owner)
            ->test(PosPage::class)
            ->call('addToCart', $this->lele->id)
            ->call('updateQty', $this->lele->id, 4)
            ->assertSet("cart.{$this->lele->id}.qty", 4)
            ->assertSet("cart.{$this->lele->id}.subtotal", 72000.00)
            ->call('updateQty', $this->lele->id, 100) // exceeds stock (5), should clamp to 5
            ->assertSet("cart.{$this->lele->id}.qty", 5)
            ->assertSet("cart.{$this->lele->id}.subtotal", 90000.00)
            ->call('updateQty', $this->lele->id, 0) // zero should remove from cart
            ->assertCount('cart', 0);
    }

    /**
     * Test cannot add out of stock product to cart.
     */
    public function test_cannot_add_out_of_stock_product_to_cart(): void
    {
        Livewire::actingAs($this->owner)
            ->test(PosPage::class)
            ->call('addToCart', $this->outOfStockProduct->id)
            ->assertCount('cart', 0);
    }

    /**
     * Test cannot add more quantity than available stock.
     */
    public function test_cannot_exceed_available_stock_in_cart(): void
    {
        // $this->lele has stock = 5
        Livewire::actingAs($this->owner)
            ->test(PosPage::class)
            ->call('addToCart', $this->lele->id)
            ->call('addToCart', $this->lele->id)
            ->call('addToCart', $this->lele->id)
            ->call('addToCart', $this->lele->id)
            ->call('addToCart', $this->lele->id) // 5th item
            ->assertSet("cart.{$this->lele->id}.qty", 5)
            ->call('addToCart', $this->lele->id) // 6th attempt (exceeds stock)
            ->assertSet("cart.{$this->lele->id}.qty", 5)
            ->call('incrementQty', $this->lele->id) // increment attempt
            ->assertSet("cart.{$this->lele->id}.qty", 5);
    }

    /**
     * Test removing an item and clearing the entire cart.
     */
    public function test_can_remove_item_and_clear_cart(): void
    {
        Livewire::actingAs($this->owner)
            ->test(PosPage::class)
            ->call('addToCart', $this->lele->id)
            ->call('addToCart', $this->esTeh->id)
            ->assertCount('cart', 2)
            ->call('removeFromCart', $this->lele->id)
            ->assertCount('cart', 1)
            ->call('clearCart')
            ->assertCount('cart', 0);
    }

    /**
     * Test opening and closing payment modal.
     */
    public function test_can_open_and_close_payment_modal(): void
    {
        Livewire::actingAs($this->owner)
            ->test(PosPage::class)
            ->call('addToCart', $this->lele->id)
            ->call('openPaymentModal')
            ->assertSet('showPaymentModal', true)
            ->call('closePaymentModal')
            ->assertSet('showPaymentModal', false);
    }

    /**
     * Test quick cash shortcuts and change calculation.
     */
    public function test_quick_cash_buttons_and_change_calculation(): void
    {
        Livewire::actingAs($this->owner)
            ->test(PosPage::class)
            ->call('addToCart', $this->lele->id) // 18.000
            ->call('openPaymentModal')
            ->call('setPaidAmount', 50000)
            ->assertSet('paidAmount', 50000)
            ->assertSet('changeAmount', 32000.00)
            ->call('setExactCash')
            ->assertSet('paidAmount', 18000.00)
            ->assertSet('changeAmount', 0.00);
    }

    /**
     * Test successful cash checkout, stock decrement, order items snapshot, and sale mutation.
     */
    public function test_successful_cash_checkout_with_stock_deduction_and_mutation(): void
    {
        $initialStockLele = $this->lele->stock; // 5
        $initialStockEsTeh = $this->esTeh->stock; // 20

        Livewire::actingAs($this->owner)
            ->test(PosPage::class)
            ->call('addToCart', $this->lele->id) // 1x 18.000
            ->call('addToCart', $this->esTeh->id) // 1x 5.000 (Total: 23.000)
            ->call('openPaymentModal')
            ->call('setPaidAmount', 50000)
            ->call('checkout')
            ->assertHasNoErrors()
            ->assertSet('showPaymentModal', false)
            ->assertSet('showSuccessModal', true)
            ->assertCount('cart', 0);

        // Verify Order in database
        $this->assertDatabaseHas('orders', [
            'total_amount' => 23000.00,
            'paid_amount' => 50000.00,
            'change_amount' => 27000.00,
            'payment_method' => 'cash',
            'status' => 'paid',
        ]);

        // Verify Order Items snapshot
        $this->assertDatabaseHas('order_items', [
            'product_id' => $this->lele->id,
            'quantity' => 1,
            'cost_price' => 10000.00,
            'unit_price' => 18000.00,
            'subtotal' => 18000.00,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $this->esTeh->id,
            'quantity' => 1,
            'cost_price' => 1500.00,
            'unit_price' => 5000.00,
            'subtotal' => 5000.00,
        ]);

        // Verify stock decremented
        $this->assertEquals($initialStockLele - 1, $this->lele->fresh()->stock);
        $this->assertEquals($initialStockEsTeh - 1, $this->esTeh->fresh()->stock);

        // Verify stock mutations recorded
        $this->assertDatabaseHas('stock_mutations', [
            'product_id' => $this->lele->id,
            'type' => 'sale',
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('stock_mutations', [
            'product_id' => $this->esTeh->id,
            'type' => 'sale',
            'quantity' => 1,
        ]);
    }

    /**
     * Test successful QRIS cashless checkout.
     */
    public function test_successful_qris_checkout(): void
    {
        Livewire::actingAs($this->owner)
            ->test(PosPage::class)
            ->call('addToCart', $this->lele->id)
            ->call('openPaymentModal')
            ->call('setPaymentMethod', 'qris')
            ->call('checkout')
            ->assertHasNoErrors()
            ->assertSet('showSuccessModal', true);

        $this->assertDatabaseHas('orders', [
            'total_amount' => 18000.00,
            'paid_amount' => 18000.00,
            'change_amount' => 0.00,
            'payment_method' => 'qris',
            'status' => 'paid',
        ]);
    }

    /**
     * Test validation failure when cash paid amount is less than total amount.
     */
    public function test_cannot_checkout_cash_with_insufficient_paid_amount(): void
    {
        Livewire::actingAs($this->owner)
            ->test(PosPage::class)
            ->call('addToCart', $this->lele->id) // 18.000
            ->call('openPaymentModal')
            ->call('setPaidAmount', 10000) // Less than 18.000
            ->call('checkout')
            ->assertHasErrors(['paidAmount']);

        $this->assertEquals(0, \App\Models\Order::count());
    }

    /**
     * Test rollback and error when stock becomes insufficient before checkout completes.
     */
    public function test_atomic_rollback_when_stock_becomes_insufficient(): void
    {
        $component = Livewire::actingAs($this->owner)
            ->test(PosPage::class)
            ->call('addToCart', $this->lele->id); // Cart has 1 item

        // Manually simulate another process setting stock to 0
        $this->lele->update(['stock' => 0]);

        $component->call('openPaymentModal')
            ->call('setExactCash')
            ->call('checkout')
            ->assertHasErrors(['cart']);

        $this->assertEquals(0, \App\Models\Order::count());
        $this->assertEquals(0, \App\Models\OrderItem::count());
    }

    /**
     * Test checkout dispatches print-receipt event and manual reprint works.
     */
    public function test_checkout_and_reprint_dispatches_print_receipt_event(): void
    {
        Livewire::actingAs($this->owner)
            ->test(PosPage::class)
            ->call('addToCart', $this->lele->id)
            ->call('openPaymentModal')
            ->call('setExactCash')
            ->call('checkout')
            ->assertDispatched('print-receipt')
            ->call('printLastReceipt')
            ->assertDispatched('print-receipt');
    }

    /**
     * Test thermal receipt blade view renders all required restaurant, order, and calculation information.
     */
    public function test_thermal_receipt_view_renders_accurate_order_data_and_totals(): void
    {
        $order = \App\Models\Order::create([
            'order_number' => 'BL-20260929-0099',
            'total_amount' => 23000.00,
            'paid_amount' => 50000.00,
            'change_amount' => 27000.00,
            'payment_method' => 'cash',
            'status' => 'paid',
            'ordered_at' => now(),
        ]);

        \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->lele->id,
            'quantity' => 1,
            'cost_price' => 10000.00,
            'unit_price' => 18000.00,
            'subtotal' => 18000.00,
        ]);

        \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->esTeh->id,
            'quantity' => 1,
            'cost_price' => 1500.00,
            'unit_price' => 5000.00,
            'subtotal' => 5000.00,
        ]);

        $renderedHtml = view('filament.pages.partials.receipt', ['order' => $order->load('orderItems.product')])->render();

        $this->assertStringContainsString('BUDHE LAMONGAN', $renderedHtml);
        $this->assertStringContainsString('BL-20260929-0099', $renderedHtml);
        $this->assertStringContainsString('Pecel Lele Goreng', $renderedHtml);
        $this->assertStringContainsString('Es Teh Manis', $renderedHtml);
        $this->assertStringContainsString('Rp 23.000', $renderedHtml);
        $this->assertStringContainsString('Rp 50.000', $renderedHtml);
        $this->assertStringContainsString('Rp 27.000', $renderedHtml);
        $this->assertStringContainsString('Matur Nuwun sampun rawuh', $renderedHtml);
    }
}


