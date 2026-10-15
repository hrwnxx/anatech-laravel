<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with the data carried over from the
     * original PHP version (database/seeders/data/*.json). Order matters:
     * every table is seeded after the tables its foreign keys point to.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            GadgetSeeder::class,
            PurchaseSeeder::class,
            ReviewSeeder::class,
        ]);
    }

    /**
     * Reads one of the JSON files in database/seeders/data.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function data(string $name): array
    {
        return json_decode(file_get_contents(database_path("seeders/data/{$name}.json")), true);
    }
}
