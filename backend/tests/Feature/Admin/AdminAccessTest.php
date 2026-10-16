<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The old PHP version left its admin AJAX files open to anyone.
 * These tests prove every admin endpoint is now locked down.
 */
class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public static function adminEndpoints(): array
    {
        return [
            ['get', '/api/admin/dashboard'],
            ['get', '/api/admin/gadgets'],
            ['post', '/api/admin/gadgets'],
            ['get', '/api/admin/purchases'],
            ['post', '/api/admin/purchases'],
            ['get', '/api/admin/reviews'],
            ['post', '/api/admin/reviews/bulk'],
            ['get', '/api/admin/customers'],
            ['get', '/api/admin/users'],
            ['post', '/api/admin/users'],
        ];
    }

    #[DataProvider('adminEndpoints')]
    public function test_guests_are_rejected(string $method, string $uri): void
    {
        $this->json($method, $uri)->assertUnauthorized();
    }

    #[DataProvider('adminEndpoints')]
    public function test_customers_are_forbidden(string $method, string $uri): void
    {
        $this->actingAs(User::factory()->create())
            ->json($method, $uri)
            ->assertForbidden();
    }

    public function test_admins_are_allowed_in(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/admin/dashboard')
            ->assertOk();
    }
}
