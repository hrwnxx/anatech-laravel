<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Every seeded account gets this password so anyone on the team can log in.
     * Change it from the admin Users page if you need to.
     */
    public const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        foreach (DatabaseSeeder::data('users') as $user) {
            // forceCreate keeps the original ids, so purchases and reviews
            // still point at the right accounts.
            User::forceCreate([
                ...$user,
                'password' => self::DEMO_PASSWORD, // hashed automatically by the model's "hashed" cast
                'updated_at' => $user['created_at'],
            ]);
        }
    }
}
