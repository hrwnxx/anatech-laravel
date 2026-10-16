<?php

namespace Tests\Feature;

use App\Models\Gadget;
use App\Models\Purchase;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_review_a_gadget_they_did_not_buy(): void
    {
        $gadget = Gadget::factory()->create();

        $this->actingAs(User::factory()->create())
            ->postJson('/api/reviews', ['gadget_id' => $gadget->id, 'rating' => 5, 'comment' => 'Great'])
            ->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_buyer_can_review_and_it_waits_for_approval(): void
    {
        $purchase = Purchase::factory()->create();

        $this->actingAs($purchase->user)
            ->postJson('/api/reviews', ['gadget_id' => $purchase->gadget_id, 'rating' => 4, 'comment' => 'Solid tablet'])
            ->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('is_mine', true);
    }

    public function test_buyer_cannot_review_the_same_gadget_twice(): void
    {
        $purchase = Purchase::factory()->create();
        Review::factory()->create(['user_id' => $purchase->user_id, 'gadget_id' => $purchase->gadget_id]);

        $this->actingAs($purchase->user)
            ->postJson('/api/reviews', ['gadget_id' => $purchase->gadget_id, 'rating' => 4, 'comment' => 'Again'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('gadget_id');
    }

    public function test_review_needs_a_rating_between_1_and_5_and_a_comment(): void
    {
        $purchase = Purchase::factory()->create();

        $this->actingAs($purchase->user)
            ->postJson('/api/reviews', ['gadget_id' => $purchase->gadget_id, 'rating' => 9, 'comment' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating', 'comment']);
    }

    public function test_editing_a_review_sends_it_back_to_moderation(): void
    {
        $review = Review::factory()->create(['status' => 'approved', 'rating' => 2]);

        $this->actingAs($review->user)
            ->putJson("/api/reviews/{$review->id}", ['rating' => 5, 'comment' => 'Changed my mind'])
            ->assertOk()
            ->assertJsonPath('rating', 5)
            ->assertJsonPath('status', 'pending');
    }

    public function test_customer_cannot_edit_or_delete_someone_elses_review(): void
    {
        $review = Review::factory()->create();
        $someoneElse = User::factory()->create();

        $this->actingAs($someoneElse)
            ->putJson("/api/reviews/{$review->id}", ['rating' => 1, 'comment' => 'Hacked'])
            ->assertForbidden();

        $this->actingAs($someoneElse)->deleteJson("/api/reviews/{$review->id}")->assertForbidden();

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'comment' => $review->comment]);
    }

    public function test_customer_can_delete_their_own_review(): void
    {
        $review = Review::factory()->create();

        $this->actingAs($review->user)->deleteJson("/api/reviews/{$review->id}")->assertNoContent();

        $this->assertModelMissing($review);
    }

    public function test_customer_sees_their_own_purchases_and_reviews(): void
    {
        $customer = User::factory()->create();
        Purchase::factory()->count(2)->create(['user_id' => $customer->id]);
        Purchase::factory()->archived()->create(['user_id' => $customer->id]);
        Purchase::factory()->create();  // someone else's
        Review::factory()->pending()->create(['user_id' => $customer->id]);

        $this->actingAs($customer)->getJson('/api/my/purchases')->assertOk()->assertJsonCount(2);
        $this->actingAs($customer)->getJson('/api/my/reviews')->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.status', 'pending');
    }
}
