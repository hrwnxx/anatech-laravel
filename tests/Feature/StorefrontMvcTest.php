<?php

namespace Tests\Feature;

use App\Models\Gadget;
use App\Models\Purchase;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontMvcTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Accept', 'text/html');
    }

    public function test_catalog_renders_active_gadgets_and_applies_search_filters(): void
    {
        Gadget::factory()->create(['brand' => 'Samsung', 'gadget_name' => 'Galaxy S26']);
        Gadget::factory()->archived()->create(['brand' => 'Samsung', 'gadget_name' => 'Old Galaxy']);
        Gadget::factory()->create(['brand' => 'Apple', 'gadget_name' => 'iPhone']);

        $this->get('/?keyword=galaxy')
            ->assertOk()
            ->assertViewIs('catalog.index')
            ->assertSee('Galaxy S26')
            ->assertDontSee('Old Galaxy')
            ->assertDontSee('iPhone');
    }

    public function test_gadget_page_renders_only_approved_reviews(): void
    {
        $gadget = Gadget::factory()->create();
        Review::factory()->create([
            'gadget_id' => $gadget->id,
            'comment' => 'Visible review',
            'status' => 'approved',
        ]);
        Review::factory()->pending()->create([
            'gadget_id' => $gadget->id,
            'comment' => 'Private pending review',
        ]);

        $this->get("/gadgets/{$gadget->id}")
            ->assertOk()
            ->assertViewIs('catalog.show')
            ->assertSee('Visible review')
            ->assertDontSee('Private pending review');
    }

    public function test_login_authenticates_active_users_and_rejects_inactive_users(): void
    {
        User::factory()->create(['email' => 'active@example.com']);

        $this->post('/login', [
            'email' => 'active@example.com',
            'password' => 'password',
        ])->assertRedirect('/');

        $this->assertAuthenticated();
        auth()->logout();

        User::factory()->inactive()->create(['email' => 'inactive@example.com']);

        $this->from('/login')->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('email');
    }

    public function test_customer_account_pages_require_login(): void
    {
        $this->get('/my/purchases')->assertRedirect('/login');
        $this->get('/my/reviews')->assertRedirect('/login');
    }

    public function test_customer_can_create_update_and_delete_a_review_through_web_forms(): void
    {
        $purchase = Purchase::factory()->create();
        $customer = $purchase->user;

        $this->actingAs($customer)
            ->post("/gadgets/{$purchase->gadget_id}/reviews", [
                'rating' => 4,
                'comment' => 'Good product',
            ])
            ->assertRedirect("/gadgets/{$purchase->gadget_id}");

        $review = Review::query()->firstOrFail();
        $this->assertSame('pending', $review->status);

        $this->from('/my/reviews')
            ->actingAs($customer)
            ->patch("/reviews/{$review->id}", [
                'rating' => 5,
                'comment' => 'Updated review',
            ])
            ->assertRedirect('/my/reviews');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => 'Updated review',
            'status' => 'pending',
        ]);

        $this->from('/my/reviews')
            ->actingAs($customer)
            ->delete("/reviews/{$review->id}")
            ->assertRedirect('/my/reviews');

        $this->assertModelMissing($review);
    }
}
