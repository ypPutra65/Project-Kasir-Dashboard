<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Makanan Utama',
                'slug' => 'makanan-utama',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Minuman',
                'slug' => 'minuman',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Sambal & Ekstra',
                'slug' => 'sambal-ekstra',
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
