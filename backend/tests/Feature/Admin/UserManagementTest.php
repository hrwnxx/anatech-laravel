<?php

namespace Tests\Feature\Admin;

use App\Models\Purchase;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function test_admin_can_create_a_user_and_the_password_is_hashed(): void
    {
        $this->postJson('/api/admin/users', [
            'name' => 'Staff Member',
            'email' => 'staff@example.com',
            'password' => 'secret123',
            'role' => 'admin',
        ])->assertCreated()->assertJsonPath('status', 'active');

        $user = User::where('email', 'staff@example.com')->firstOrFail();
        $this->assertNotSame('secret123', $user->password);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function test_staff_accounts_need_a_password(): void
    {
        $this->postJson('/api/admin/users', [
            'name' => 'Staff Member',
            'email' => 'staff@example.com',
            'role' => 'admin',
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'password' => 'Staff accounts need a password, because staff log in with a password.',
        ]);
    }

    public function test_customer_accounts_can_be_created_without_a_password(): void
    {
        $this->postJson('/api/admin/users', [
            'name' => 'Maria Santos',
            'email' => 'maria@gmail.com',
            'role' => 'customer',
        ])->assertCreated()
            ->assertJsonPath('has_password', false)
            ->assertJsonPath('has_google_login', false);
    }

    public function test_changing_an_email_unlinks_the_old_google_account(): void
    {
        $customer = User::factory()->create(['email' => 'maria@gmial.com', 'google_id' => 'google-123']);
        $changes = ['name' => $customer->name, 'role' => 'customer', 'status' => 'active'];

        $this->putJson("/api/admin/users/{$customer->id}", [...$changes, 'email' => 'maria@gmail.com'])
            ->assertOk()
            ->assertJsonPath('has_google_login', false);

        $this->assertNull($customer->fresh()->google_id);
    }

    public function test_editing_without_changing_the_email_keeps_the_google_account_linked(): void
    {
        $customer = User::factory()->create(['email' => 'maria@gmail.com', 'google_id' => 'google-123']);

        $this->putJson("/api/admin/users/{$customer->id}", [
            'name' => 'Maria S.',
            'email' => 'Maria@Gmail.com',
            'role' => 'customer',
            'status' => 'active',
        ])->assertOk()->assertJsonPath('has_google_login', true);
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/admin/users', [
            'name' => 'Someone',
            'email' => 'taken@example.com',
            'password' => 'secret123',
            'role' => 'customer',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_users_can_be_searched_and_filtered(): void
    {
        User::factory()->create(['name' => 'Juan Dela Cruz']);
        User::factory()->inactive()->create(['name' => 'Yanna']);

        $this->getJson('/api/admin/users?keyword=juan')->assertJsonCount(1);
        $this->getJson('/api/admin/users?role=admin')->assertJsonCount(1);
        $this->getJson('/api/admin/users?status=inactive')->assertJsonCount(1)->assertJsonPath('0.name', 'Yanna');
    }

    public function test_admin_can_edit_a_user_and_reset_their_password(): void
    {
        $user = User::factory()->create();

        $this->putJson("/api/admin/users/{$user->id}", [
            'name' => 'New Name',
            'email' => $user->email,
            'role' => 'customer',
            'status' => 'inactive',
        ])->assertOk()->assertJsonPath('status', 'inactive');

        $this->putJson("/api/admin/users/{$user->id}/password", ['password' => 'newpass123'])->assertNoContent();
        $this->assertTrue(Hash::check('newpass123', $user->fresh()->password));
    }

    public function test_admin_cannot_lock_themselves_out(): void
    {
        $this->putJson("/api/admin/users/{$this->admin->id}", [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role' => 'customer',
            'status' => 'active',
        ])->assertUnprocessable();

        $this->deleteJson("/api/admin/users/{$this->admin->id}")->assertUnprocessable();

        $this->assertSame('admin', $this->admin->fresh()->role);
    }

    public function test_admin_can_delete_another_user(): void
    {
        $user = User::factory()->create();

        $this->deleteJson("/api/admin/users/{$user->id}")->assertNoContent();

        $this->assertModelMissing($user);
    }

    public function test_customers_page_shows_activity_counts_and_history(): void
    {
        $customer = User::factory()->create();
        Purchase::factory()->count(2)->create(['user_id' => $customer->id]);
        Review::factory()->create(['user_id' => $customer->id]);

        $this->getJson('/api/admin/customers')
            ->assertJsonCount(1)  // the admin is not listed
            ->assertJsonPath('0.purchases_count', 2)
            ->assertJsonPath('0.reviews_count', 1);

        $this->getJson("/api/admin/customers/{$customer->id}/purchases")->assertOk()->assertJsonCount(2);
    }
}
