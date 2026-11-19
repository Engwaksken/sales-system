<?php

namespace Tests\Feature;

use Tests\TestCase;

class CsrfTokenRefreshTest extends TestCase
{
    public function test_csrf_token_endpoint_returns_the_session_token_uncached(): void
    {
        $response = $this->getJson('/csrf-token');

        $response->assertOk()->assertJsonStructure(['token']);
        $this->assertSame(session()->token(), $response->json('token'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }
}
