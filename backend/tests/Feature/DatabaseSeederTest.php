<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_seeded_account_uses_the_documented_demo_password(): void
    {
        Storage::fake('public');

        $this->seed();

        $users = User::all();
        $this->assertCount(4, $users);
        foreach ($users as $user) {
            $this->assertTrue(
                Hash::check(UserSeeder::DEMO_PASSWORD, $user->password),
                "{$user->email} should use the demo password from README.md.",
            );
        }
    }

    public function test_seeded_admin_can_log_in_on_the_staff_login(): void
    {
        Storage::fake('public');
        $this->seed();

        $this->postJson('/api/staff/login', [
            'email' => 'admin@gadgetshop.com',
            'password' => UserSeeder::DEMO_PASSWORD,
        ])->assertOk()->assertJsonPath('role', 'admin');
    }
}
