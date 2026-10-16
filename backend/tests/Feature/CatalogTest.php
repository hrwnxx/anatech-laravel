<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Gadget;
use App\Models\Purchase;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_lists_only_active_gadgets_with_their_stats(): void
    {
        $gadget = Gadget::factory()->withStock(7)->create();
        Gadget::factory()->archived()->create();
        Purchase::factory()->create(['gadget_id' => $gadget->id, 'quantity' => 2]);
        Review::factory()->create(['gadget_id' => $gadget->id, 'rating' => 4]);
        Review::factory()->create(['gadget_id' => $gadget->id, 'rating' => 5]);
        Review::factory()->pending()->create(['gadget_id' => $gadget->id, 'rating' => 1]);

        $this->getJson('/api/gadgets')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $gadget->id)
            ->assertJsonPath('0.stock', 7)
            ->assertJsonPath('0.units_sold', 2)
            ->assertJsonPath('0.avg_rating', 4.5)  // the pending 1-star review doesn't count
            ->assertJsonPath('0.review_count', 2);
    }

    public function test_catalog_can_be_searched_and_filtered(): void
    {
        $phones = Category::factory()->create(['name' => 'Phone']);
        Gadget::factory()->create(['brand' => 'Samsung', 'gadget_name' => 'Galaxy S26', 'category_id' => $phones->id]);
        Gadget::factory()->create(['brand' => 'Apple', 'gadget_name' => 'iPad Air']);

        $this->getJson('/api/gadgets?keyword=galaxy')->assertJsonCount(1)->assertJsonPath('0.gadget_name', 'Galaxy S26');
        $this->getJson('/api/gadgets?keyword=phone')->assertJsonCount(1);  // matches the category name
        $this->getJson('/api/gadgets?brand=Apple')->assertJsonCount(1)->assertJsonPath('0.brand', 'Apple');
        $this->getJson("/api/gadgets?category_id={$phones->id}")->assertJsonCount(1);
    }

    public function test_catalog_can_be_sorted_by_price(): void
    {
        Gadget::factory()->create(['price' => 500]);
        Gadget::factory()->create(['price' => 100]);

        $this->getJson('/api/gadgets?sort=price_asc')->assertJsonPath('0.price', 100);
        $this->getJson('/api/gadgets?sort=price_desc')->assertJsonPath('0.price', 500);
    }

    public function test_gadget_page_shows_approved_reviews_with_masked_names(): void
    {
        $gadget = Gadget::factory()->create();
        $juan = User::factory()->create(['name' => 'Juan Dela Cruz']);
        Review::factory()->create(['gadget_id' => $gadget->id, 'user_id' => $juan->id, 'rating' => 5]);
        Review::factory()->pending()->create(['gadget_id' => $gadget->id]);

        $this->getJson("/api/gadgets/{$gadget->id}")
            ->assertOk()
            ->assertJsonPath('gadget.id', $gadget->id)
            ->assertJsonCount(1, 'reviews')
            ->assertJsonPath('reviews.0.reviewer', 'Juan C.')
            ->assertJsonPath('rating_counts.5', 1)
            ->assertJsonPath('viewer.logged_in', false);
    }

    public function test_gadget_page_tells_a_buyer_they_can_review(): void
    {
        $gadget = Gadget::factory()->create();
        $buyer = User::factory()->create();
        Purchase::factory()->create(['gadget_id' => $gadget->id, 'user_id' => $buyer->id]);

        $this->actingAs($buyer)
            ->getJson("/api/gadgets/{$gadget->id}")
            ->assertJsonPath('viewer.has_purchased', true)
            ->assertJsonPath('viewer.my_review', null);
    }

    public function test_archived_gadget_page_is_not_found(): void
    {
        $gadget = Gadget::factory()->archived()->create();

        $this->getJson("/api/gadgets/{$gadget->id}")->assertNotFound();
    }

    public function test_filter_lists_are_available(): void
    {
        Gadget::factory()->create(['brand' => 'Xiaomi']);
        Gadget::factory()->archived()->create(['brand' => 'Nokia']);

        $this->getJson('/api/categories')->assertOk()->assertJsonCount(2);
        $this->getJson('/api/brands')->assertOk()->assertExactJson(['Xiaomi']);
    }
}
