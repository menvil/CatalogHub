<?php

declare(strict_types=1);

namespace Tests\Feature\SystemPages;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class ApplicationErrorPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/admin/central/__foundation-error/500', static function (): never {
            throw new \RuntimeException('database-password=secret');
        });
        Route::get('/__foundation-error/500', static function (): never {
            throw new \RuntimeException('token=secret');
        });
    }

    public function test_admin_500_is_generic_and_correlates_response_with_logs(): void
    {
        $contexts = [];
        Event::listen(MessageLogged::class, static function (MessageLogged $event) use (&$contexts): void {
            $contexts[] = $event->context;
        });

        $response = $this->withHeader('X-Request-ID', 'central-error-500')
            ->get('/admin/central/__foundation-error/500')
            ->assertStatus(500)
            ->assertHeader('X-Request-ID', 'central-error-500')
            ->assertSee('data-admin-error="500"', false)
            ->assertSee('Request ID:')
            ->assertSee('central-error-500')
            ->assertDontSee('database-password')
            ->assertDontSee('RuntimeException');

        $this->assertStringNotContainsString('stack trace', strtolower($response->getContent()));
        $this->assertTrue(collect($contexts)->contains(
            static fn (array $context): bool => ($context['request_id'] ?? null) === 'central-error-500',
        ));
    }

    public function test_invalid_request_id_is_replaced_and_not_reflected(): void
    {
        $response = $this->withHeader('X-Request-ID', 'invalid request id!')
            ->get('/__foundation-error/500')
            ->assertStatus(500)
            ->assertDontSee('invalid request id!');

        $requestId = $response->headers->get('X-Request-ID');
        $this->assertIsString($requestId);
        $this->assertMatchesRegularExpression('/\A[0-9a-f-]{36}\z/i', $requestId);
        $response->assertSee($requestId);
    }

    public function test_successful_responses_also_expose_the_request_id_header(): void
    {
        $this->withHeader('X-Request-ID', 'request-success-123')
            ->get('/up')
            ->assertOk()
            ->assertHeader('X-Request-ID', 'request-success-123');
    }
}
