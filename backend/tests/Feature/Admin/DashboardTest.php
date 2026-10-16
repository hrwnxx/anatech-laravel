<?php

namespace Tests\Feature\Admin;

use App\Models\Gadget;
use App\Models\Purchase;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_numbers(): void
    {
        $phone = Gadget::factory()->withStock(2)->create(['price' => 1000]);
        $tablet = Gadget::factory()->withStock(50)->create(['price' => 500]);

        Purchase::factory()->create(['gadget_id' => $phone->id, 'quantity' => 3, 'purchase_date' => '2026-09-10']);
        Purchase::factory()->create(['gadget_id' => $tablet->id, 'quantity' => 1, 'purchase_date' => '2026-10-01']);
        Purchase::factory()->archived()->create(['gadget_id' => $tablet->id, 'quantity' => 100]);  // ignored
        Review::factory()->create(['rating' => 4]);
        Review::factory()->create(['rating' => 5]);
        Review::factory()->pending()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('stats.revenue', 3500)  // 3 x 1000 + 1 x 500
            ->assertJsonPath('stats.total_purchases', 2)
            ->assertJsonPath('stats.pending_reviews', 1)
            ->assertJsonPath('stats.average_rating', 4.5)
            ->assertJsonPath('top_selling.0.id', $phone->id)
            ->assertJsonPath('top_selling.0.total_sold', 3)
            ->assertJsonPath('monthly_sales.0.month', '2026-10')
            ->assertJsonPath('monthly_sales.1.revenue', 3000)
            ->assertJsonPath('low_stock.0.id', $phone->id);
    }
}
