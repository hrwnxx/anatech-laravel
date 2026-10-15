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
            'catalog' => ['/'],
            'login' => ['/login'],
            'gadget page' => ['/gadgets/5'],
            'admin page' => ['/admin/gadgets'],
        ];
    }

    #[DataProvider('reactPageUrls')]
    public function test_page_urls_load_the_react_app(string $url): void
    {
        $this->withHeader('Accept', 'text/html')
            ->get($url)
            ->assertOk()
            ->assertSee('<div id="app"></div>', false);
    }

    public function test_unknown_api_urls_return_a_json_404_instead_of_the_react_app(): void
    {
        $this->getJson('/api/does-not-exist')
            ->assertNotFound()
            ->assertDontSee('<div id="app">', false);
    }
}
