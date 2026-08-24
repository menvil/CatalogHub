<?php

declare(strict_types=1);

namespace Tests\Feature\Central;

use App\Data\CentralCatalog\CentralBrandInput;
use App\Enums\CentralBrandStatus;
use App\Enums\UserRole;
use App\Http\Requests\CentralAdmin\CentralBrandFormRequest;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class CentralBrandFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_ca_013_routes_use_the_expected_http_contract_and_model_binding(): void
    {
        $routes = [
            'central.brands.create' => ['GET', 'admin/central/brands/create'],
            'central.brands.store' => ['POST', 'admin/central/brands'],
            'central.brands.edit' => ['GET', 'admin/central/brands/{brand}/edit'],
            'central.brands.update' => ['PATCH', 'admin/central/brands/{brand}'],
        ];

        foreach ($routes as $name => [$method, $uri]) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route);
            $this->assertContains($method, $route->methods());
            $this->assertSame($uri, $route->uri());
            $this->assertContains('can:catalog.products.manage', $route->gatherMiddleware());
        }

        $this->assertSame('central.brands.create', Route::getRoutes()->match(
            request()->create('/admin/central/brands/create', 'GET'),
        )->getName());
        $this->assertTrue(Route::has('central.brands.show'));
    }

    public function test_create_screen_renders_the_shared_accessible_form_without_status_input(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('central.brands.create'))
            ->assertOk()
            ->assertSee('data-screen-id="CA-013"', false)
            ->assertSee('Create Brand')
            ->assertSee('Create a canonical brand in the central catalog.')
            ->assertSeeInOrder(['Dashboard', 'Brands', 'Create'])
            ->assertSee('data-admin-form-state', false)
            ->assertSee('action="/admin/central/brands"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="slug"', false)
            ->assertSee('name="website_url"', false)
            ->assertSee('name="country_code"', false)
            ->assertSee('autocomplete="organization"', false)
            ->assertSee('autocomplete="country"', false)
            ->assertSee('Leave blank to generate from the brand name.')
            ->assertSee('New brands are created as Draft.')
            ->assertSee('Cancel')
            ->assertDontSee('name="status"', false)
            ->assertDontSee('normalized_name')
            ->assertDontSee('normalized_name_hash');

        $this->assertCancelTargets($response->getContent(), route('central.brands.index', absolute: false));
    }

    public function test_store_creates_a_normalized_draft_and_redirects_to_edit_with_one_time_flash(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->post(route('central.brands.store'), [
                'name' => 'Samsung Electronics',
                'slug' => '',
                'website_url' => 'https://www.samsung.com',
                'country_code' => 'kr',
                'status' => CentralBrandStatus::Active->value,
                'normalized_name' => 'attacker-controlled',
                'normalized_name_hash' => str_repeat('0', 64),
                'created_at' => '2000-01-01 00:00:00',
            ]);

        $brand = CentralBrand::query()->sole();

        $response
            ->assertRedirect(route('central.brands.edit', $brand))
            ->assertSessionHas('success', 'Brand created.');
        $this->assertSame('Samsung Electronics', $brand->name);
        $this->assertSame('samsung-electronics', $brand->slug);
        $this->assertSame('https://www.samsung.com', $brand->website_url);
        $this->assertSame('KR', $brand->country_code);
        $this->assertSame(CentralBrandStatus::Draft, $brand->status);
        $this->assertSame('samsung electronics', $brand->normalized_name);
        $this->assertNotSame(str_repeat('0', 64), $brand->normalized_name_hash);
        $this->assertTrue($brand->created_at->greaterThan(CarbonImmutable::parse('2026-01-01')));

        $this->get(route('central.brands.edit', $brand))
            ->assertOk()
            ->assertSee('Brand created.')
            ->assertSee('value="samsung-electronics"', false)
            ->assertSee('value="KR"', false)
            ->assertSee('Draft');

        $this->get(route('central.brands.edit', $brand))
            ->assertOk()
            ->assertDontSee('Brand created.');
    }

    public function test_explicit_slug_is_normalized_by_the_action(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('central.brands.store'), [
                'name' => 'Samsung Phones',
                'slug' => ' Samsung_Phones ',
                'website_url' => '',
                'country_code' => '',
            ])
            ->assertRedirect();

        $brand = CentralBrand::query()->sole();
        $this->assertSame('samsung-phones', $brand->slug);
        $this->assertNull($brand->website_url);
        $this->assertNull($brand->country_code);
    }

    #[DataProvider('invalidHttpShapeProvider')]
    public function test_form_request_rejects_invalid_http_shapes(array $payload, string $field): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('central.brands.create'))
            ->post(route('central.brands.store'), $payload)
            ->assertRedirect(route('central.brands.create'))
            ->assertSessionHasErrors($field);

        $this->assertDatabaseCount('central_brands', 0);
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function invalidHttpShapeProvider(): iterable
    {
        yield 'name is required' => [['slug' => 'missing-name'], 'name'];
        yield 'name must be a string' => [['name' => ['Samsung']], 'name'];
        yield 'name respects storage length' => [['name' => str_repeat('a', 256)], 'name'];
        yield 'slug must be a string or null' => [['name' => 'Samsung', 'slug' => ['samsung']], 'slug'];
        yield 'website must be a string or null' => [['name' => 'Samsung', 'website_url' => ['https://example.com']], 'website_url'];
        yield 'country must be a string or null' => [['name' => 'Samsung', 'country_code' => ['KR']], 'country_code'];
        yield 'country respects storage length' => [['name' => 'Samsung', 'country_code' => 'KOR'], 'country_code'];
    }

    public function test_form_request_exposes_only_known_fields_as_typed_input(): void
    {
        $request = CentralBrandFormRequest::create('/admin/central/brands', 'POST', [
            'name' => 'Samsung',
            'slug' => null,
            'website_url' => null,
            'status' => CentralBrandStatus::Active->value,
            'normalized_name' => 'attacker-controlled',
            'normalized_name_hash' => str_repeat('0', 64),
        ]);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));
        $request->validateResolved();

        $input = $request->brandInput();

        $this->assertInstanceOf(CentralBrandInput::class, $input);
        $this->assertTrue($input->hasWebsiteUrl);
        $this->assertFalse($input->hasCountryCode);
        $this->assertSame([
            'name' => 'Samsung',
            'slug' => null,
            'website_url' => null,
        ], $input->actionPayload());
    }

    #[DataProvider('invalidCreatePayloadProvider')]
    public function test_store_maps_representative_validation_failures_to_fields(
        array $payload,
        string $field,
    ): void {
        $response = $this->actingAs(User::factory()->create())
            ->from(route('central.brands.create'))
            ->post(route('central.brands.store'), $payload);

        $response
            ->assertRedirect(route('central.brands.create'))
            ->assertSessionHasErrors($field);
        $this->assertDatabaseCount('central_brands', 0);
    }

    /** @return iterable<string, array{array<string, string>, string}> */
    public static function invalidCreatePayloadProvider(): iterable
    {
        yield 'empty name' => [['name' => '', 'slug' => 'empty-name'], 'name'];
        yield 'invalid slug' => [['name' => 'Samsung', 'slug' => 'Samsung!!!'], 'slug'];
        yield 'invalid URL' => [['name' => 'Samsung', 'slug' => 'samsung', 'website_url' => 'not-a-url'], 'website_url'];
        yield 'invalid country semantics' => [['name' => 'Samsung', 'slug' => 'samsung', 'country_code' => 'K1'], 'country_code'];
        yield 'invalid generated slug' => [['name' => '品牌', 'slug' => ''], 'slug'];
    }

    public function test_duplicate_name_unicode_identity_and_slug_errors_preserve_old_input(): void
    {
        CentralBrand::factory()->create(['name' => 'ÉLECTRO', 'slug' => 'samsung']);
        $this->actingAs(User::factory()->create());

        $this->from(route('central.brands.create'))
            ->post(route('central.brands.store'), [
                'name' => 'électro',
                'slug' => 'electro-new',
                'website_url' => 'https://submitted.example',
                'country_code' => 'fr',
            ])
            ->assertRedirect(route('central.brands.create'))
            ->assertSessionHasErrors('name')
            ->assertSessionHasInput('name', 'électro')
            ->assertSessionHasInput('website_url', 'https://submitted.example');

        $this->from(route('central.brands.create'))
            ->post(route('central.brands.store'), [
                'name' => 'Different Brand',
                'slug' => 'samsung',
            ])
            ->assertRedirect(route('central.brands.create'))
            ->assertSessionHasErrors('slug');

        $this->assertDatabaseCount('central_brands', 1);
    }

    public function test_invalid_create_never_persists_a_partial_row(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('central.brands.create'))
            ->post(route('central.brands.store'), [
                'name' => 'Valid New Name',
                'slug' => 'valid-new-name',
                'website_url' => 'invalid',
                'country_code' => 'KR',
            ])
            ->assertSessionHasErrors('website_url');

        $this->assertDatabaseMissing('central_brands', ['name' => 'Valid New Name']);
    }

    public function test_edit_screen_renders_persisted_values_and_read_only_status_for_every_lifecycle(): void
    {
        $user = User::factory()->create();

        foreach (CentralBrandStatus::cases() as $status) {
            $brand = CentralBrand::factory()->create([
                'name' => $status->label().' Brand',
                'slug' => $status->value.'-brand',
                'status' => $status,
                'website_url' => 'https://'.$status->value.'.example.com',
                'country_code' => 'US',
            ]);

            $this->actingAs($user)
                ->get(route('central.brands.edit', $brand))
                ->assertOk()
                ->assertSee('data-screen-id="CA-013"', false)
                ->assertSee('Edit Brand')
                ->assertSee('value="'.$brand->name.'"', false)
                ->assertSee('value="'.$brand->slug.'"', false)
                ->assertSee('value="'.$brand->website_url.'"', false)
                ->assertSee('value="US"', false)
                ->assertSee('data-admin-status-badge', false)
                ->assertSee($status->label())
                ->assertSee('href="'.route('central.brands.show', $brand, absolute: false).'"', false)
                ->assertSee('name="_method" value="PATCH"', false)
                ->assertDontSee('name="status"', false);
        }
    }

    public function test_edit_breadcrumb_and_cancel_return_to_brand_detail_while_create_cancel_returns_to_list(): void
    {
        $brand = CentralBrand::factory()->create(['name' => 'Samsung']);
        $user = User::factory()->create();

        $editResponse = $this->actingAs($user)
            ->get(route('central.brands.edit', $brand))
            ->assertOk()
            ->assertSee('href="'.route('central.brands.show', $brand, absolute: false).'"', false)
            ->assertSeeInOrder(['Brands', 'Samsung', 'Edit'])
            ->assertSee('Cancel');
        $this->assertCancelTargets($editResponse->getContent(), route('central.brands.show', $brand, absolute: false));

        $createResponse = $this->get(route('central.brands.create'))
            ->assertOk()
            ->assertSee('Cancel');
        $this->assertCancelTargets($createResponse->getContent(), route('central.brands.index', absolute: false));
    }

    public function test_update_changes_canonical_fields_keeps_slug_stable_and_flashes_once(): void
    {
        $brand = CentralBrand::factory()->active()->create([
            'name' => 'Samsung',
            'slug' => 'samsung',
            'website_url' => 'https://samsung.com',
            'country_code' => 'KR',
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->patch(route('central.brands.update', $brand), [
                'name' => 'Samsung Electronics',
                'slug' => 'samsung',
                'website_url' => 'https://www.samsung.com',
                'country_code' => 'us',
                'status' => CentralBrandStatus::Archived->value,
                'normalized_name' => 'attacker-controlled',
                'normalized_name_hash' => str_repeat('0', 64),
                'created_at' => '2000-01-01 00:00:00',
            ]);

        $response
            ->assertRedirect(route('central.brands.edit', $brand))
            ->assertSessionHas('success', 'Brand updated.');

        $brand->refresh();
        $this->assertSame('Samsung Electronics', $brand->name);
        $this->assertSame('samsung', $brand->slug);
        $this->assertSame('https://www.samsung.com', $brand->website_url);
        $this->assertSame('US', $brand->country_code);
        $this->assertSame(CentralBrandStatus::Active, $brand->status);
        $this->assertSame('samsung electronics', $brand->normalized_name);
        $this->assertNotSame(str_repeat('0', 64), $brand->normalized_name_hash);

        $this->get(route('central.brands.edit', $brand))
            ->assertOk()
            ->assertSee('Brand updated.');
        $this->get(route('central.brands.edit', $brand))
            ->assertOk()
            ->assertDontSee('Brand updated.');
    }

    public function test_update_allows_draft_active_and_archived_without_changing_status(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (CentralBrandStatus::cases() as $status) {
            $brand = CentralBrand::factory()->create([
                'name' => $status->label().' Original',
                'slug' => $status->value.'-original',
                'status' => $status,
            ]);

            $this->patch(route('central.brands.update', $brand), [
                'name' => $status->label().' Updated',
                'slug' => $status->value.'-original',
                'website_url' => '',
                'country_code' => '',
            ])->assertRedirect(route('central.brands.edit', $brand));

            $this->assertSame($status, $brand->fresh()->status);
            $this->assertSame($status->label().' Updated', $brand->fresh()->name);
        }
    }

    public function test_partial_update_preserves_omitted_optional_fields(): void
    {
        $brand = CentralBrand::factory()->active()->create([
            'name' => 'Samsung',
            'slug' => 'samsung',
            'website_url' => 'https://www.samsung.com',
            'country_code' => 'KR',
        ]);

        $this->actingAs(User::factory()->create())
            ->patch(route('central.brands.update', $brand), [
                'name' => 'Samsung Electronics',
                'slug' => 'samsung',
            ])
            ->assertRedirect(route('central.brands.edit', $brand));

        $brand->refresh();
        $this->assertSame('Samsung Electronics', $brand->name);
        $this->assertSame('https://www.samsung.com', $brand->website_url);
        $this->assertSame('KR', $brand->country_code);
        $this->assertSame(CentralBrandStatus::Active, $brand->status);
    }

    public function test_partial_update_explicitly_clears_blank_optional_fields(): void
    {
        $brand = CentralBrand::factory()->active()->create([
            'name' => 'Samsung',
            'slug' => 'samsung',
            'website_url' => 'https://www.samsung.com',
            'country_code' => 'KR',
        ]);

        $this->actingAs(User::factory()->create())
            ->patch(route('central.brands.update', $brand), [
                'name' => 'Samsung',
                'slug' => 'samsung',
                'website_url' => '',
                'country_code' => '',
            ])
            ->assertRedirect(route('central.brands.edit', $brand));

        $brand->refresh();
        $this->assertNull($brand->website_url);
        $this->assertNull($brand->country_code);
        $this->assertSame(CentralBrandStatus::Active, $brand->status);
    }

    public function test_invalid_update_preserves_old_input_and_leaves_the_entire_brand_unchanged(): void
    {
        $brand = CentralBrand::factory()->archived()->create([
            'name' => 'Samsung',
            'slug' => 'samsung',
            'website_url' => 'https://samsung.com',
            'country_code' => 'KR',
        ]);
        $before = $brand->getAttributes();

        $this->actingAs(User::factory()->create())
            ->from(route('central.brands.edit', $brand))
            ->patch(route('central.brands.update', $brand), [
                'name' => 'Samsung Electronics',
                'slug' => 'samsung-electronics',
                'website_url' => 'not-a-url',
                'country_code' => 'K1',
                'status' => CentralBrandStatus::Active->value,
            ])
            ->assertRedirect(route('central.brands.edit', $brand))
            ->assertSessionHasErrors(['website_url', 'country_code'])
            ->assertSessionHasInput('name', 'Samsung Electronics')
            ->assertSessionHasInput('website_url', 'not-a-url');

        $this->assertEquals($before, $brand->fresh()->getAttributes());
    }

    public function test_update_maps_duplicate_name_and_slug_to_their_fields(): void
    {
        CentralBrand::factory()->create(['name' => 'Samsung', 'slug' => 'samsung']);
        $brand = CentralBrand::factory()->create(['name' => 'LG', 'slug' => 'lg']);
        $this->actingAs(User::factory()->create());

        $this->from(route('central.brands.edit', $brand))
            ->patch(route('central.brands.update', $brand), [
                'name' => 'samsung',
                'slug' => 'lg',
            ])
            ->assertSessionHasErrors('name');

        $this->from(route('central.brands.edit', $brand))
            ->patch(route('central.brands.update', $brand), [
                'name' => 'LG',
                'slug' => 'samsung',
            ])
            ->assertSessionHasErrors('slug');

        $this->assertSame('LG', $brand->fresh()->name);
        $this->assertSame('lg', $brand->fresh()->slug);
    }

    public function test_all_four_routes_require_authentication_and_catalog_permission(): void
    {
        $brand = CentralBrand::factory()->create();
        $routes = [
            ['get', route('central.brands.create')],
            ['post', route('central.brands.store')],
            ['get', route('central.brands.edit', $brand)],
            ['patch', route('central.brands.update', $brand)],
        ];

        foreach ($routes as [$method, $url]) {
            $this->{$method}($url)->assertRedirect(route('filament.central.auth.login'));
        }

        $translator = User::factory()->create(['role' => UserRole::Translator]);

        foreach ($routes as [$method, $url]) {
            $this->actingAs($translator)->{$method}($url)->assertForbidden();
        }
    }

    private function assertCancelTargets(string $html, string $expectedUrl): void
    {
        $pattern = sprintf(
            '/<a\b(?=[^>]*\bdata-brand-form-cancel\b)(?=[^>]*\bhref="%s")[^>]*>/',
            preg_quote($expectedUrl, '/'),
        );

        $this->assertMatchesRegularExpression($pattern, $html);
    }
}
