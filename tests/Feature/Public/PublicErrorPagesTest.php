<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use App\Http\Responses\PublicErrorResponse;
use App\Models\Site;
use App\Support\PublicSite\PublicErrorContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

    public function test_site_admin_error_with_runtime_context_keeps_the_admin_error_response(): void
    {
        $request = Request::create('/admin/site/foundation-error');
        $route = new Route('GET', '/admin/site/foundation-error', static fn (): null => null);
        $route->name('filament.site.foundation-error');
        $request->setRouteResolver(static fn (): Route => $route);
        $request->attributes->set(PublicErrorContext::class, new PublicErrorContext('Site Admin', '/admin/site'));
        $response = response('Admin not found', 404);

        $rendered = app(PublicErrorResponse::class)->render(
            $response,
            new NotFoundHttpException,
            $request,
        );

        $this->assertSame('Admin not found', $rendered->getContent());
        $this->assertStringNotContainsString('data-public-error=', $rendered->getContent());
    }

    public function test_invalid_request_identifiers_are_not_reflected_by_public_500_page(): void
    {
        Site::factory()->active()->withRuntimeContext(['en-US'])->create([
            'domain' => 'request-id-errors.test',
        ]);

        foreach (['bad request id!', str_repeat('x', 129)] as $invalidRequestId) {
            $response = $this->withHeader('X-Request-ID', $invalidRequestId)
                ->get('http://request-id-errors.test/en-US/__foundation-error/500')
                ->assertStatus(500)
                ->assertDontSee($invalidRequestId);

            $this->assertMatchesRegularExpression(
                '/Request ID:\s*<code>[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}<\/code>/i',
                $response->getContent(),
            );
        }
    }
}
