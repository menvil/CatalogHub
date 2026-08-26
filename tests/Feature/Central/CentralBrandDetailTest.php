<?php

declare(strict_types=1);

namespace Tests\Feature\Central;

use App\Enums\CentralBrandStatus;
use App\Enums\UserRole;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Support\CountryReference;
use Tests\TestCase;

final class CentralBrandDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_ca_012_routes_use_explicit_http_commands_and_catalog_permission(): void
    {
        $routes = [
            'central.brands.show' => ['GET', 'admin/central/brands/{brand}'],
            'central.brands.activate' => ['POST', 'admin/central/brands/{brand}/activate'],
            'central.brands.archive' => ['POST', 'admin/central/brands/{brand}/archive'],
            'central.brands.restore' => ['POST', 'admin/central/brands/{brand}/restore'],
        ];

        foreach ($routes as $name => [$method, $uri]) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route);
            $this->assertContains($method, $route->methods());
            $this->assertSame($uri, $route->uri());
            $this->assertContains('can:catalog.brands.manage', $route->gatherMiddleware());
            $this->assertNotContains('can:catalog.products.manage', $route->gatherMiddleware());
        }

        $this->assertSame('central.brands.create', Route::getRoutes()->match(
            request()->create('/admin/central/brands/create', 'GET'),
        )->getName());
        $this->assertFalse(Route::has('central.brands.status'));
    }

    public function test_authorized_user_can_view_canonical_detail_data_and_internal_fields_stay_hidden(): void
    {
        $brand = CentralBrand::factory()->active()->create([
            'name' => 'Samsung',
            'normalized_name' => 'internal-only-identity',
            'normalized_name_hash' => str_repeat('a', 64),
            'slug' => 'samsung',
            'website_url' => 'https://www.samsung.com/global',
            'country_id' => CountryReference::id('KR'),
            'founded_year' => 1938,
            'support_url' => 'https://www.samsung.com/support/',
            'contact_email' => 'support@example.com',
            'primary_color' => '#1428A0',
            'created_at' => CarbonImmutable::parse('2026-08-20 09:15:00 UTC'),
            'updated_at' => CarbonImmutable::parse('2026-08-24 13:30:00 UTC'),
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('central.brands.show', $brand))
            ->assertOk()
            ->assertSee('data-screen-id="CA-012"', false)
            ->assertDontSee('>CA-012<', false)
            ->assertSee('data-admin-detail-layout', false)
            ->assertSeeInOrder(['Dashboard', 'Brands', 'Samsung'])
            ->assertSee('Canonical brand in the central catalog.')
            ->assertSee('Samsung')
            ->assertSee('samsung')
            ->assertSee('Active')
            ->assertSee('href="https://www.samsung.com/global"', false)
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertSee('South Korea (KR)')
            ->assertSee('1938')
            ->assertSee('href="https://www.samsung.com/support/"', false)
            ->assertSee('support@example.com')
            ->assertSee('#1428A0')
            ->assertSee('2026-08-20 09:15 UTC')
            ->assertSee('2026-08-24 13:30 UTC')
            ->assertSee((string) $brand->getKey())
            ->assertSee('Edit Brand')
            ->assertSee('href="'.route('central.brands.edit', $brand, absolute: false).'"', false)
            ->assertDontSee('internal-only-identity')
            ->assertDontSee(str_repeat('a', 64))
            ->assertDontSee('normalized_name')
            ->assertDontSee('normalized_name_hash');

        /** @var CentralBrand $viewBrand */
        $viewBrand = $response->viewData('brand');
        $this->assertFalse($viewBrand->relationLoaded('products'));
    }

    public function test_null_metadata_uses_an_em_dash_without_errors(): void
    {
        $brand = CentralBrand::factory()->create([
            'name' => 'Metadata Free Brand',
            'website_url' => null,
            'country_id' => null,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('central.brands.show', $brand))
            ->assertOk()
            ->assertSeeInOrder(['Country', '—', 'Founded', '—', 'Website', '—', 'Support URL', '—', 'Contact email', '—', 'Primary color', '—']);
    }

    public function test_unsafe_legacy_website_is_plain_text_and_never_an_executable_link(): void
    {
        $brand = CentralBrand::factory()->create([
            'name' => 'Legacy Website Brand',
            'website_url' => 'javascript:alert(1)',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('central.brands.show', $brand))
            ->assertOk()
            ->assertSee('javascript:alert(1)')
            ->assertDontSee('href="javascript:alert(1)"', false);
    }

    public function test_unsafe_legacy_support_url_is_plain_text_and_primary_color_requires_canonical_shape_for_swatch(): void
    {
        $brand = CentralBrand::factory()->create([
            'name' => 'Legacy Profile Brand',
            'support_url' => 'javascript:alert(1)',
            'primary_color' => 'red',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('central.brands.show', $brand))
            ->assertOk()
            ->assertSee('javascript:alert(1)')
            ->assertDontSee('href="javascript:alert(1)"', false)
            ->assertSee('red')
            ->assertDontSee('background-color: red', false);
    }

    public function test_usage_count_includes_only_products_referencing_the_current_brand(): void
    {
        $brand = CentralBrand::factory()->create(['name' => 'Counted Brand']);
        $other = CentralBrand::factory()->create(['name' => 'Other Brand']);
        CentralProduct::factory()->count(3)->for($brand, 'brand')->create();
        CentralProduct::factory()->count(2)->for($other, 'brand')->create();
        CentralProduct::factory()->create(['central_brand_id' => null]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('central.brands.show', $brand))
            ->assertOk()
            ->assertSee('data-products-count="3"', false)
            ->assertSee('3 canonical products reference this brand.')
            ->assertDontSee('Product List');

        /** @var CentralBrand $viewBrand */
        $viewBrand = $response->viewData('brand');
        $this->assertSame(3, $viewBrand->products_count);
        $this->assertFalse($viewBrand->relationLoaded('products'));
    }

    public function test_zero_usage_has_clear_copy_and_no_fake_product_cta(): void
    {
        $brand = CentralBrand::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('central.brands.show', $brand))
            ->assertOk()
            ->assertSee('data-products-count="0"', false)
            ->assertSee('No canonical products reference this brand yet.')
            ->assertDontSee('Create Product');
    }

    public function test_lifecycle_controls_only_render_valid_intents_for_each_state(): void
    {
        $user = User::factory()->create();
        $expectations = [
            CentralBrandStatus::Draft->value => [['Activate Brand', 'Archive Brand'], ['Restore Brand']],
            CentralBrandStatus::Active->value => [['Archive Brand'], ['Activate Brand', 'Restore Brand']],
            CentralBrandStatus::Archived->value => [['Restore Brand'], ['Activate Brand', 'Archive Brand']],
        ];

        foreach ($expectations as $status => [$visible, $hidden]) {
            $brand = CentralBrand::factory()->create(['status' => $status]);
            $response = $this->actingAs($user)->get(route('central.brands.show', $brand))->assertOk();

            foreach ($visible as $label) {
                $response->assertSee($label);
            }

            foreach ($hidden as $label) {
                $response->assertDontSee($label);
            }
        }
    }

    public function test_lifecycle_controls_use_csrf_post_forms_without_a_status_payload(): void
    {
        $brand = CentralBrand::factory()->draft()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('central.brands.show', $brand))
            ->assertOk()
            ->assertSee('method="POST" action="'.route('central.brands.activate', $brand, absolute: false).'"', false)
            ->assertSee('method="POST" action="'.route('central.brands.archive', $brand, absolute: false).'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('form="activate-brand-form"', false)
            ->assertSee('form="archive-brand-form"', false)
            ->assertDontSee('name="status"', false);
    }

    public function test_activate_archive_and_restore_use_explicit_post_workflows(): void
    {
        $user = User::factory()->create();
        $draft = CentralBrand::factory()->draft()->create();

        $this->actingAs($user)
            ->post(route('central.brands.activate', $draft))
            ->assertRedirect(route('central.brands.show', $draft))
            ->assertSessionHas('success', 'Brand activated.');
        $this->assertSame(CentralBrandStatus::Active, $draft->fresh()->status);

        $this->post(route('central.brands.archive', $draft))
            ->assertRedirect(route('central.brands.show', $draft))
            ->assertSessionHas('success', 'Brand archived.');
        $this->assertSame(CentralBrandStatus::Archived, $draft->fresh()->status);

        $this->post(route('central.brands.restore', $draft))
            ->assertRedirect(route('central.brands.show', $draft))
            ->assertSessionHas('success', 'Brand restored to Draft.');
        $this->assertSame(CentralBrandStatus::Draft, $draft->fresh()->status);
    }

    public function test_draft_can_be_archived_directly(): void
    {
        $brand = CentralBrand::factory()->draft()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('central.brands.archive', $brand))
            ->assertRedirect(route('central.brands.show', $brand));

        $this->assertSame(CentralBrandStatus::Archived, $brand->fresh()->status);
    }

    public function test_restore_rejects_an_active_brand_without_flashing_success(): void
    {
        $brand = CentralBrand::factory()->active()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('central.brands.restore', $brand))
            ->assertRedirect(route('central.brands.show', $brand))
            ->assertSessionHasErrors(['status' => 'Only archived brands can be restored.'])
            ->assertSessionMissing('success');

        $this->assertSame(CentralBrandStatus::Active, $brand->fresh()->status);
    }

    public function test_archived_brand_cannot_be_activated_directly_and_detail_shows_status_error(): void
    {
        $brand = CentralBrand::factory()->archived()->create();

        $response = $this->actingAs(User::factory()->create())
            ->from(route('central.brands.show', $brand))
            ->post(route('central.brands.activate', $brand))
            ->assertRedirect(route('central.brands.show', $brand))
            ->assertSessionHasErrors([
                'status' => 'Archived brands must be restored before they can be activated.',
            ]);

        $this->assertSame(CentralBrandStatus::Archived, $brand->fresh()->status);

        $this->followRedirects($response)
            ->assertOk()
            ->assertSee('data-lifecycle-error', false)
            ->assertSee('Archived brands must be restored before they can be activated.');
    }

    public function test_detail_and_lifecycle_routes_require_authentication_and_catalog_permission(): void
    {
        $brand = CentralBrand::factory()->draft()->create();
        $routes = [
            ['get', route('central.brands.show', $brand)],
            ['post', route('central.brands.activate', $brand)],
            ['post', route('central.brands.archive', $brand)],
            ['post', route('central.brands.restore', $brand)],
        ];

        foreach ($routes as [$method, $url]) {
            $this->{$method}($url)->assertRedirect(route('filament.central.auth.login'));
        }

        $translator = User::factory()->create(['role' => UserRole::Translator]);

        foreach ($routes as [$method, $url]) {
            $this->actingAs($translator)->{$method}($url)->assertForbidden();
        }
    }

    public function test_unknown_brand_ids_use_standard_not_found_responses(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/central/brands/999999999')->assertNotFound();
        $this->post('/admin/central/brands/999999999/archive')->assertNotFound();
    }
}
