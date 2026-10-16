<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (DatabaseSeeder::data('categories') as $category) {
            Category::forceCreate($category);
        }
    }
}
