<?php

namespace Database\Seeders;

use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DatabaseSeeder::data('reviews') as $review) {
            Review::forceCreate([
                ...$review,
                'updated_at' => $review['created_at'],
            ]);
        }
    }
}
