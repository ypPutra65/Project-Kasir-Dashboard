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
}
