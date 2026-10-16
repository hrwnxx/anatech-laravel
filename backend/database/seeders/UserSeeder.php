<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Every seeded account (staff and customers) uses this password, so
     * anyone on the team can log in right after `php artisan migrate --seed`.
     * Documented in README.md, section 2.
     */
    public const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        foreach (DatabaseSeeder::data('users') as $user) {
            // forceCreate keeps the original ids, so purchases and reviews
            // still point at the right accounts. The User model's "hashed"
            // cast hashes the demo password.
            User::forceCreate([
                ...$user,
                'password' => self::DEMO_PASSWORD,
                'updated_at' => $user['created_at'],
            ]);
        }
    }
}
