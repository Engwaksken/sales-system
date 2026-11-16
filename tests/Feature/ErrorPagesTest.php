<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_missing_page_shows_branded_not_found_page(): void
    {
        $this->get('/this-page-does-not-exist-123')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('Go to home page')
            ->assertDontSee('NotFoundHttpException');
    }

    public function test_server_error_shows_friendly_page_without_details(): void
    {
        config(['app.debug' => false]);
        Route::get('/__boom', fn () => throw new \RuntimeException('secret internal detail'));

        $this->get('/__boom')
            ->assertStatus(500)
            ->assertSee('Something went wrong')
            ->assertSee('Try again')
            ->assertDontSee('secret internal detail');
    }

    public function test_forbidden_page_shows_custom_reason(): void
    {
        Route::get('/__denied', fn () => abort(403, 'Only managers can open reports.'));

        $this->get('/__denied')->assertForbidden()->assertSee('Access denied')->assertSee('Only managers can open reports.');
    }

    public function test_offline_page_is_public(): void
    {
        $this->get('/offline')->assertOk()->assertSee('You are offline');
    }

    public function test_api_errors_are_json_even_without_accept_header(): void
    {
        $this->get('/api/mobile/does-not-exist')
            ->assertNotFound()
            ->assertHeader('content-type', 'application/json');
    }

    public function test_layouts_load_network_status_helper(): void
    {
        $this->get('/login')->assertOk()->assertSee('net-banner', false)->assertSee('sw.js', false);
    }
}
