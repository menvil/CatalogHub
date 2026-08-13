<?php

declare(strict_types=1);

namespace Tests\Feature\Sites;

use App\Enums\UserRole;
use App\Http\Middleware\ResolveSiteRuntimeContext;
use App\Models\Site;
use App\Models\User;
use App\Support\Sites\SiteRuntimeContext;
use Database\Seeders\Demo\MultiCategorySiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use LogicException;
use Tests\TestCase;

final class SiteContextMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_request_receives_the_resolved_context_through_dependency_injection(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);
        Route::middleware(['web', ResolveSiteRuntimeContext::class])
            ->get('/_runtime/{locale}', static function (SiteRuntimeContext $context, Request $request): array {
                return [
                    ...$context->__debugInfo(),
                    'application_locale' => app()->getLocale(),
                    'application_timezone' => date_default_timezone_get(),
                    'attribute_is_same' => $request->attributes->get(SiteRuntimeContext::class) === $context,
                ];
            });

        $this->get('http://tech-compare.test/_runtime/en-US')
            ->assertOk()
            ->assertJsonPath('site_code', 'tech-compare-global')
            ->assertJsonPath('host', 'tech-compare.test')
            ->assertJsonPath('resolved_locale', 'en-US')
            ->assertJsonPath('currency_code', 'USD')
            ->assertJsonPath('application_locale', 'en-US')
            ->assertJsonPath('application_timezone', 'UTC')
            ->assertJsonPath('attribute_is_same', true);
    }

    public function test_unknown_public_host_returns_404_and_context_does_not_leak_or_change_process_defaults(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);
        $originalLocale = app()->getLocale();
        $originalTimezone = date_default_timezone_get();

        $this->get('http://tech-compare.test/en-US')->assertOk();

        self::assertSame($originalLocale, app()->getLocale());
        self::assertSame($originalTimezone, date_default_timezone_get());
        $this->get('http://unknown-site.test/en-US')->assertNotFound();

        self::assertSame($originalLocale, app()->getLocale());
        self::assertSame($originalTimezone, date_default_timezone_get());
        self::assertFalse(app(Request::class)->attributes->has(SiteRuntimeContext::class));

        try {
            app(SiteRuntimeContext::class);
            self::fail('A site context leaked after the unknown-host response.');
        } catch (LogicException $exception) {
            self::assertSame('Site runtime context is unavailable for this request.', $exception->getMessage());
        }
    }

    public function test_site_admin_uses_the_authenticated_site_while_central_has_no_site_requirement(): void
    {
        $site = Site::factory()->active()->withRuntimeContext(['de-DE'], 'de-DE')->create([
            'name' => 'Bound administration site',
            'currency_code' => 'EUR',
            'timezone' => 'Europe/Berlin',
        ]);
        $otherSite = Site::factory()->active()->withRuntimeContext()->create();
        $siteAdmin = User::factory()->siteAdmin($site)->create();

        $this->actingAs($siteAdmin)
            ->get("/admin/site?site_id={$otherSite->id}")
            ->assertForbidden()
            ->assertDontSee($otherSite->name);

        $this->get("/admin/site?site_id={$site->id}")
            ->assertOk()
            ->assertSee('Bound administration site');

        $central = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $this->actingAs($central)->get('/admin/central')->assertOk();

        $centralRoute = app('router')->getRoutes()->getByName('filament.central.pages.home');
        self::assertNotNull($centralRoute);
        self::assertNotContains(ResolveSiteRuntimeContext::class, $centralRoute->gatherMiddleware());
    }

    public function test_actual_public_and_site_routes_register_the_context_middleware(): void
    {
        foreach (['public.landing', 'public.home', 'public.search', 'filament.site.pages.home'] as $routeName) {
            $route = app('router')->getRoutes()->getByName($routeName);

            self::assertNotNull($route);
            self::assertContains(ResolveSiteRuntimeContext::class, $route->gatherMiddleware());
        }
    }
}
