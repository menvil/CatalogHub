<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_known_site_404_keeps_safe_identity_and_unknown_host_stays_generic(): void
    {
        Site::factory()->active()->withRuntimeContext(['en-US'])->create([
            'domain' => 'known-errors.test',
            'name' => 'Known Error Site',
        ]);

        $known = $this->get('http://known-errors.test/en-US/missing-page')->assertNotFound();
        $knownHtml = $known->getContent();
        $this->assertStringContainsString('data-public-error="404"', $knownHtml);
        $this->assertStringContainsString('Known Error Site', $knownHtml);
        $this->assertStringContainsString('href="https://known-errors.test/en-US"', $knownHtml);
        $this->assertStringNotContainsString('central-admin', $knownHtml);
        $this->assertStringNotContainsString('site-admin', $knownHtml);

        $unknown = $this->get('http://absent-errors.test/en-US')->assertNotFound();
        $unknownHtml = $unknown->getContent();
        $this->assertStringContainsString('data-public-error="404"', $unknownHtml);
        $this->assertStringNotContainsString('Known Error Site', $unknownHtml);
        $this->assertStringNotContainsString('known-errors.test', $unknownHtml);
    }

    public function test_public_500_and_maintenance_responses_are_safe(): void
    {
        Site::factory()->active()->withRuntimeContext(['en-US'])->create([
            'domain' => 'system-errors.test',
            'name' => 'System Error Site',
        ]);
        $this->withHeader('X-Request-ID', 'public-request-500')
            ->get('http://system-errors.test/en-US/__foundation-error/500')
            ->assertStatus(500)
            ->assertSee('data-public-error="500"', false)
            ->assertSee('public-request-500')
            ->assertDontSee('database-password')
            ->assertDontSee('RuntimeException');

        $this->get('http://system-errors.test/en-US/__foundation-error/503')
            ->assertStatus(503)
            ->assertSee('data-public-error="503"', false)
            ->assertSee('System Error Site')
            ->assertDontSee('internal-maintenance-detail');
    }
}
