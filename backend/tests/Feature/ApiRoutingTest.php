<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiRoutingTest extends TestCase
{
    public function test_unknown_api_urls_return_a_json_404(): void
    {
        $this->getJson('/api/does-not-exist')->assertNotFound();
    }

    public function test_the_backend_no_longer_serves_any_pages(): void
    {
        $this->get('/')->assertNotFound();
        $this->get('/login')->assertNotFound();
        $this->get('/admin/gadgets')->assertNotFound();
    }

    public function test_guests_calling_a_protected_api_route_get_a_json_401(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
        $this->getJson('/api/admin/dashboard')->assertUnauthorized();
    }
}
