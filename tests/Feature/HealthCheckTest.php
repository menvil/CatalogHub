<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_check_returns_healthy_status(): void
    {
        Carbon::setTestNow('2026-07-16T12:00:00+00:00');

        $this->getJson('/health')
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'app' => 'CatalogHub',
                'environment' => app()->environment(),
                'timestamp' => '2026-07-16T12:00:00+00:00',
            ]);
    }

    public function test_health_check_does_not_expose_secrets(): void
    {
        $content = $this->getJson('/health')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('APP_KEY', $content);
        $this->assertStringNotContainsString('DB_PASSWORD', $content);
        $this->assertStringNotContainsString('REDIS_PASSWORD', $content);

        foreach ([config('app.key'), config('database.connections.pgsql.password'), config('database.redis.default.password')] as $secret) {
            if (is_string($secret) && $secret !== '') {
                $this->assertStringNotContainsString($secret, $content);
            }
        }
    }
}
