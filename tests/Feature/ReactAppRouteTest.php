<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReactAppRouteTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function reactPageUrls(): array
    {
        return [
            'admin page' => ['/admin/gadgets'],
        ];
    }

    #[DataProvider('reactPageUrls')]
    public function test_admin_page_urls_load_the_react_app(string $url): void
    {
        $this->withHeader('Accept', 'text/html')
            ->get($url)
            ->assertOk()
            ->assertSee('<div id="app"></div>', false);
    }

    public function test_login_page_uses_a_laravel_view(): void
    {
        $this->get('/login')->assertOk()->assertViewIs('auth.login');
    }

    public function test_unknown_api_urls_return_a_json_404_instead_of_the_react_app(): void
    {
        $this->getJson('/api/does-not-exist')
            ->assertNotFound()
            ->assertDontSee('<div id="app">', false);
    }
}
