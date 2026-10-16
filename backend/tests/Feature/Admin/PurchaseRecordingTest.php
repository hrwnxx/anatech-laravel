<?php

namespace Tests\Feature\Admin;

use App\Models\Gadget;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PurchaseRecordingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_recording_a_purchase_reduces_stock_and_keeps_the_sale_price(): void
    {
        $customer = User::factory()->create();
        $gadget = Gadget::factory()->withStock(10)->create(['price' => 1500]);

        $this->postJson('/api/admin/purchases', [
            'user_id' => $customer->id,
            'gadget_id' => $gadget->id,
            'quantity' => 3,
            'purchase_date' => now()->toDateString(),
        ])->assertCreated()
            ->assertJsonPath('customer.id', $customer->id)
            ->assertJsonPath('unit_price', 1500)
            ->assertJsonPath('total', 4500);

        $this->assertSame(7, $gadget->inventory->fresh()->quantity);

        // Changing the price later must not change what this sale was worth.
        $gadget->update(['price' => 9999]);
        $this->assertEquals(1500, Purchase::first()->unit_price);
    }

    public function test_cannot_sell_more_than_is_in_stock(): void
    {
        $gadget = Gadget::factory()->withStock(2)->create();

        $this->postJson('/api/admin/purchases', [
            'user_id' => User::factory()->create()->id,
            'gadget_id' => $gadget->id,
            'quantity' => 5,
            'purchase_date' => now()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('quantity');

        $this->assertDatabaseCount('purchases', 0);
        $this->assertSame(2, $gadget->inventory->fresh()->quantity);
    }

    public function test_new_customer_account_is_created_with_the_purchase(): void
    {
        $gadget = Gadget::factory()->create();

        $this->postJson('/api/admin/purchases', [
            'new_customer' => true,
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'password' => 'secret123',
            'gadget_id' => $gadget->id,
            'quantity' => 1,
            'purchase_date' => now()->toDateString(),
        ])->assertCreated()->assertJsonPath('customer.email', 'maria@example.com');

        $this->assertDatabaseHas('users', ['email' => 'maria@example.com', 'role' => 'customer']);

        // ...and the new customer can log in with the temporary password.
        $this->assertTrue(Auth::guard('web')->validate(['email' => 'maria@example.com', 'password' => 'secret123']));
    }

    public function test_customer_with_gmail_needs_no_temporary_password(): void
    {
        $gadget = Gadget::factory()->create();

        $this->postJson('/api/admin/purchases', [
            'new_customer' => true,
            'name' => 'Maria Santos',
            'email' => 'Maria.Santos@Gmail.com',
            'gadget_id' => $gadget->id,
            'quantity' => 1,
            'purchase_date' => now()->toDateString(),
        ])->assertCreated()->assertJsonPath('customer.email', 'maria.santos@gmail.com');

        $customer = User::where('email', 'maria.santos@gmail.com')->firstOrFail();
        $this->assertNull($customer->password);
    }

    public function test_new_customer_is_not_created_when_the_stock_check_fails(): void
    {
        $gadget = Gadget::factory()->withStock(0)->create();

        $this->postJson('/api/admin/purchases', [
            'new_customer' => true,
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'password' => 'secret123',
            'gadget_id' => $gadget->id,
            'quantity' => 1,
            'purchase_date' => now()->toDateString(),
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('users', ['email' => 'maria@example.com']);
    }

    public function test_purchase_cannot_be_recorded_for_an_admin_or_in_the_future(): void
    {
        $this->postJson('/api/admin/purchases', [
            'user_id' => User::factory()->admin()->create()->id,
            'gadget_id' => Gadget::factory()->create()->id,
            'quantity' => 1,
            'purchase_date' => now()->addDay()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors(['user_id', 'purchase_date']);
    }

    public function test_purchases_are_archived_instead_of_deleted(): void
    {
        $purchase = Purchase::factory()->create();

        $this->patchJson("/api/admin/purchases/{$purchase->id}/archive")->assertOk();
        $this->getJson('/api/admin/purchases')->assertJsonCount(0);
        $this->getJson('/api/admin/purchases?archived=1')->assertJsonCount(1);

        // An archived purchase still lets the customer review what they bought.
        $this->assertTrue($purchase->user->hasPurchased($purchase->gadget_id));

        $this->patchJson("/api/admin/purchases/{$purchase->id}/unarchive")->assertOk();
        $this->getJson('/api/admin/purchases')->assertJsonCount(1);
    }
}
