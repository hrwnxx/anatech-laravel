<?php

namespace Tests\Feature\Admin;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_pending_and_archive_tabs(): void
    {
        $juan = User::factory()->create(['name' => 'Juan Dela Cruz']);
        Review::factory()->pending()->create(['user_id' => $juan->id]);
        Review::factory()->create();
        Review::factory()->rejected()->create();

        $this->getJson('/api/admin/reviews')
            ->assertJsonCount(1)
            ->assertJsonPath('0.reviewer', 'Juan Dela Cruz');  // admins see the real name

        $this->getJson('/api/admin/reviews?status=archived')->assertJsonCount(2);
    }

    public function test_approve_and_reject(): void
    {
        $toApprove = Review::factory()->pending()->create();
        $toReject = Review::factory()->pending()->create();

        $this->patchJson("/api/admin/reviews/{$toApprove->id}/approve")->assertOk()->assertJsonPath('status', 'approved');
        $this->patchJson("/api/admin/reviews/{$toReject->id}/reject")->assertOk()->assertJsonPath('status', 'rejected');

        $this->assertDatabaseCount('reviews', 2);  // rejecting keeps the review on file
    }

    public function test_bulk_approve(): void
    {
        $reviews = Review::factory()->pending()->count(3)->create();

        $this->postJson('/api/admin/reviews/bulk', [
            'action' => 'approve',
            'ids' => $reviews->take(2)->pluck('id')->all(),
        ])->assertOk()->assertJsonPath('updated', 2);

        $this->assertSame(2, Review::approved()->count());
        $this->assertSame(1, Review::pending()->count());
    }
}
