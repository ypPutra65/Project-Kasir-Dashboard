<?php

namespace Tests\Feature;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UserSeeder::class);
        $this->owner = User::where('email', 'owner@budhelamongan.local')->first();
    }

    /**
     * Test list categories page can be rendered.
     */
    public function test_can_render_category_list_page(): void
    {
        Category::create([
            'name' => 'Makanan Utama',
            'slug' => 'makanan-utama',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($this->owner)
            ->get(CategoryResource::getUrl('index'))
            ->assertSuccessful();

        Livewire::actingAs($this->owner)
            ->test(ListCategories::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(Category::all());
    }

    /**
     * Test create category page can be rendered.
     */
    public function test_can_render_create_category_page(): void
    {
        $this->actingAs($this->owner)
            ->get(CategoryResource::getUrl('create'))
            ->assertSuccessful();
    }

    /**
     * Test creating a category via Livewire form with auto-slug generation.
     */
    public function test_can_create_category_with_auto_slug(): void
    {
        Livewire::actingAs($this->owner)
            ->test(CreateCategory::class)
            ->set('data.name', 'Paket Hemat')
            ->set('data.slug', 'paket-hemat')
            ->set('data.sort_order', 4)
            ->set('data.is_active', true)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', [
            'name' => 'Paket Hemat',
            'slug' => 'paket-hemat',
            'sort_order' => 4,
            'is_active' => 1,
        ]);
    }

    /**
     * Test create category form validation.
     */
    public function test_create_category_validation(): void
    {
        Livewire::actingAs($this->owner)
            ->test(CreateCategory::class)
            ->set('data.name', '')
            ->set('data.slug', '')
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'slug' => 'required']);
    }

    /**
     * Test edit category page can be rendered and updated.
     */
    public function test_can_edit_and_update_category(): void
    {
        $category = Category::create([
            'name' => 'Cemilan',
            'slug' => 'cemilan',
            'sort_order' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($this->owner)
            ->get(CategoryResource::getUrl('edit', ['record' => $category]))
            ->assertSuccessful();

        Livewire::actingAs($this->owner)
            ->test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->set('data.name', 'Cemilan & Gorengan')
            ->set('data.slug', 'cemilan-gorengan')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Cemilan & Gorengan',
            'slug' => 'cemilan-gorengan',
        ]);
    }

    /**
     * Test deleting a category.
     */
    public function test_can_delete_category(): void
    {
        $category = Category::create([
            'name' => 'Kategori Sementara',
            'slug' => 'kategori-sementara',
            'sort_order' => 99,
            'is_active' => false,
        ]);

        Livewire::actingAs($this->owner)
            ->test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->callAction('delete')
            ->assertHasNoActionErrors();

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }
}
