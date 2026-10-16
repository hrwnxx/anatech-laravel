<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Every account keeps the password it had in the original project:
     * users.json holds the bcrypt hash exactly as it was stored there.
     */
    public function run(): void
    {
        foreach (DatabaseSeeder::data('users') as $user) {
            // Inserted straight into the table (not through the User model),
            // because the model's "hashed" cast would hash the hash again.
            // The original ids are kept so purchases and reviews still point
            // at the right accounts.
            DB::table('users')->insert([
                ...$user,
                'updated_at' => $user['created_at'],
            ]);
        }
    }
}
