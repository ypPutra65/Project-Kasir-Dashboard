<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductResourceTest extends TestCase
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
     * Test list products page can be rendered.
     */
    public function test_can_render_product_list_page(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Pecel Lele',
            'cost_price' => 10000.00,
            'selling_price' => 18000.00,
            'stock' => 20,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($this->owner)
            ->get(ProductResource::getUrl('index'))
            ->assertSuccessful();

        Livewire::actingAs($this->owner)
            ->test(ListProducts::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$product]);
    }

    /**
     * Test filtering products by category.
     */
    public function test_can_filter_products_by_category(): void
    {
        $drinkCategory = Category::create([
            'name' => 'Minuman',
            'slug' => 'minuman',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $food = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Bebek Goreng',
            'cost_price' => 18000.00,
            'selling_price' => 30000.00,
            'stock' => 15,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        $drink = Product::create([
            'category_id' => $drinkCategory->id,
            'name' => 'Es Teh Manis',
            'cost_price' => 1500.00,
            'selling_price' => 5000.00,
            'stock' => 50,
            'min_stock_alert' => 10,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->owner)
            ->test(ListProducts::class)
            ->filterTable('category_id', $drinkCategory->id)
            ->assertCanSeeTableRecords([$drink])
            ->assertCanNotSeeTableRecords([$food]);
    }

    /**
     * Test filtering products by low stock.
     */
    public function test_can_filter_products_by_low_stock(): void
    {
        $normalProduct = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Ayam Penyet',
            'cost_price' => 13000.00,
            'selling_price' => 22000.00,
            'stock' => 20,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        $lowStockProduct = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Lele Bakar',
            'cost_price' => 11000.00,
            'selling_price' => 19000.00,
            'stock' => 3,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->owner)
            ->test(ListProducts::class)
            ->filterTable('low_stock', true)
            ->assertCanSeeTableRecords([$lowStockProduct])
            ->assertCanNotSeeTableRecords([$normalProduct]);
    }

    /**
     * Test create product page can be rendered.
     */
    public function test_can_render_create_product_page(): void
    {
        $this->actingAs($this->owner)
            ->get(ProductResource::getUrl('create'))
            ->assertSuccessful();
    }

    /**
     * Test creating a product with valid form data.
     */
    public function test_can_create_product(): void
    {
        Livewire::actingAs($this->owner)
            ->test(CreateProduct::class)
            ->set('data.category_id', $this->category->id)
            ->set('data.name', 'Gurame Bakar')
            ->set('data.cost_price', 25000)
            ->set('data.selling_price', 45000)
            ->set('data.stock', 10)
            ->set('data.min_stock_alert', 3)
            ->set('data.is_active', true)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('products', [
            'name' => 'Gurame Bakar',
            'category_id' => $this->category->id,
            'selling_price' => 45000.00,
            'cost_price' => 25000.00,
            'stock' => 10,
        ]);
    }

    /**
     * Test create product validation.
     */
    public function test_create_product_validation(): void
    {
        Livewire::actingAs($this->owner)
            ->test(CreateProduct::class)
            ->set('data.name', '')
            ->set('data.selling_price', '')
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'selling_price' => 'required', 'category_id' => 'required']);
    }

    /**
     * Test editing and updating product.
     */
    public function test_can_edit_and_update_product(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Ati Ampela',
            'cost_price' => 4000.00,
            'selling_price' => 8000.00,
            'stock' => 15,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($this->owner)
            ->get(ProductResource::getUrl('edit', ['record' => $product]))
            ->assertSuccessful();

        Livewire::actingAs($this->owner)
            ->test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->set('data.selling_price', 9000)
            ->set('data.stock', 20)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'selling_price' => 9000.00,
            'stock' => 20,
        ]);
    }

    /**
     * Test deleting a product.
     */
    public function test_can_delete_product(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Menu Hapus',
            'cost_price' => 5000.00,
            'selling_price' => 10000.00,
            'stock' => 5,
            'min_stock_alert' => 2,
            'is_active' => false,
        ]);

        Livewire::actingAs($this->owner)
            ->test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->callAction('delete')
            ->assertHasNoActionErrors();

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    /**
     * Test stock adjustment addition (in).
     */
    public function test_can_adjust_stock_in_addition(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Lele Goreng',
            'cost_price' => 10000.00,
            'selling_price' => 18000.00,
            'stock' => 10,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->owner)
            ->test(ListProducts::class)
            ->callTableAction('adjustStock', $product, data: [
                'type' => 'in',
                'quantity' => 15,
                'notes' => 'Belanja lele pagi',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 25,
        ]);

        $this->assertDatabaseHas('stock_mutations', [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 15,
            'notes' => 'Belanja lele pagi',
        ]);
    }

    /**
     * Test stock adjustment reduction (waste).
     */
    public function test_can_adjust_stock_waste_subtraction(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Bebek Bakar',
            'cost_price' => 18000.00,
            'selling_price' => 30000.00,
            'stock' => 20,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->owner)
            ->test(ListProducts::class)
            ->callTableAction('adjustStock', $product, data: [
                'type' => 'waste',
                'quantity' => 4,
                'notes' => 'Porsi sisa basi',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 16,
        ]);

        $this->assertDatabaseHas('stock_mutations', [
            'product_id' => $product->id,
            'type' => 'waste',
            'quantity' => 4,
            'notes' => 'Porsi sisa basi',
        ]);
    }

    /**
     * Test rejection when waste exceeds available stock.
     */
    public function test_cannot_adjust_stock_waste_exceeding_available_stock(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Ayam Goreng',
            'cost_price' => 13000.00,
            'selling_price' => 22000.00,
            'stock' => 5,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->owner)
            ->test(ListProducts::class)
            ->callTableAction('adjustStock', $product, data: [
                'type' => 'waste',
                'quantity' => 10,
                'notes' => 'Melebihi sisa stok',
            ])
            ->assertHasTableActionErrors(['quantity']);

        // Verify stock remains untouched
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 5,
        ]);
    }
}

