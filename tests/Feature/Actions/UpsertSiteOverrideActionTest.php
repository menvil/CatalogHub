<?php

namespace Tests\Feature\Actions;

use App\Actions\Sites\UpsertSiteOverrideAction;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\EnablesSiteProductCategories;
use Tests\TestCase;

class UpsertSiteOverrideActionTest extends TestCase
{
    use EnablesSiteProductCategories;
    use RefreshDatabase;

    #[DataProvider('entityTypes')]
    public function test_nonexistent_override_target_is_rejected(string $entityType): void
    {
        try {
            app(UpsertSiteOverrideAction::class)->handle(
                Site::factory()->create(),
                $entityType,
                PHP_INT_MAX,
                'local_title',
                null,
                'Ghost override',
            );

            $this->fail("A nonexistent {$entityType} was accepted.");
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('entity_id', $exception->errors());
        }

        $this->assertDatabaseCount('site_overrides', 0);
    }

    public function test_override_locale_must_be_enabled_for_the_site(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $this->enableProductCategory($site, $product);
        Locale::factory()->create(['code' => 'de-DE']);
        DB::table('site_locales')->insert([
            'site_id' => $site->id,
            'locale_code' => 'de-DE',
            'is_enabled' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            app(UpsertSiteOverrideAction::class)->handle(
                $site,
                'product',
                $product->id,
                'local_title',
                'de-DE',
                'Disabled locale override',
            );

            $this->fail('A disabled site locale was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('locale_code', $exception->errors());
        }

        $this->assertDatabaseCount('site_overrides', 0);
    }

    public function test_unconfigured_override_locale_is_rejected(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $this->enableProductCategory($site, $product);

        try {
            app(UpsertSiteOverrideAction::class)->handle(
                $site,
                'product',
                $product->id,
                'local_title',
                'de-DE',
                'Unconfigured locale override',
            );

            $this->fail('An unconfigured site locale was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('locale_code', $exception->errors());
        }

        $this->assertDatabaseCount('site_overrides', 0);
    }

    public function test_enabled_and_global_override_locales_are_accepted(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $this->enableProductCategory($site, $product);
        Locale::factory()->create(['code' => 'de-DE']);
        DB::table('site_locales')->insert([
            'site_id' => $site->id,
            'locale_code' => 'de-DE',
            'is_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $action = app(UpsertSiteOverrideAction::class);
        $action->handle($site, 'product', $product->id, 'local_title', 'de-DE', 'German title');
        $action->handle($site, 'product', $product->id, 'hero_text', null, 'Global hero');

        $this->assertDatabaseCount('site_overrides', 2);
        $this->assertDatabaseHas('site_overrides', [
            'site_id' => $site->id,
            'entity_type' => 'product',
            'entity_id' => $product->id,
            'field' => 'local_title',
            'locale_code' => 'de-DE',
            'value_json' => json_encode(['value' => 'German title']),
        ]);
        $this->assertDatabaseHas('site_overrides', [
            'site_id' => $site->id,
            'entity_type' => 'product',
            'entity_id' => $product->id,
            'field' => 'hero_text',
            'locale_code' => '',
            'value_json' => json_encode(['value' => 'Global hero']),
        ]);
    }

    public function test_matching_override_scope_is_updated_without_a_duplicate(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $this->enableProductCategory($site, $product);
        $action = app(UpsertSiteOverrideAction::class);

        $action->handle($site, 'product', $product->id, 'local_title', null, 'First title');
        $action->handle($site, 'product', $product->id, 'local_title', null, 'Updated title');

        $this->assertDatabaseCount('site_overrides', 1);
        $this->assertDatabaseHas('site_overrides', [
            'site_id' => $site->id,
            'entity_type' => 'product',
            'entity_id' => $product->id,
            'field' => 'local_title',
            'locale_code' => '',
            'value_json' => json_encode(['value' => 'Updated title']),
        ]);
    }

    public function test_product_override_requires_an_enabled_site_category(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create([
            'central_category_id' => CentralCategory::factory()->create()->id,
        ]);

        try {
            app(UpsertSiteOverrideAction::class)->handle($site, 'product', $product->id, 'local_title', null, 'Out of scope');

            $this->fail('An override outside the enabled site categories was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('entity_id', $exception->errors());
        }

        $this->assertDatabaseCount('site_overrides', 0);
    }

    public function test_disabled_site_category_rejects_product_override(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $this->enableProductCategory($site, $product, false);

        $this->expectException(ValidationException::class);
        app(UpsertSiteOverrideAction::class)->handle($site, 'product', $product->id, 'local_title', null, 'Disabled category');
    }

    public function test_override_can_be_cleared_after_its_product_category_is_disabled(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $this->enableProductCategory($site, $product);
        $action = app(UpsertSiteOverrideAction::class);
        $action->handle($site, 'product', $product->id, 'local_title', null, 'Local title');

        DB::table('site_categories')->where('site_id', $site->id)->update(['is_enabled' => false]);
        $action->handle($site, 'product', $product->id, 'local_title', null, '');

        $this->assertDatabaseCount('site_overrides', 0);
    }

    #[DataProvider('oversizedFields')]
    public function test_override_field_length_limits_are_enforced(string $field, int $maximum): void
    {
        try {
            app(UpsertSiteOverrideAction::class)->handle(
                Site::factory()->create(),
                'product',
                CentralProduct::factory()->create()->id,
                $field,
                null,
                str_repeat('x', $maximum + 1),
            );

            $this->fail("An oversized {$field} override was accepted.");
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('value', $exception->errors());
        }

        $this->assertDatabaseCount('site_overrides', 0);
    }

    /** @return array<string, array{string}> */
    public static function entityTypes(): array
    {
        return [
            'product' => ['product'],
            'category' => ['category'],
            'brand' => ['brand'],
        ];
    }

    /** @return array<string, array{string, int}> */
    public static function oversizedFields(): array
    {
        return [
            'meta title' => ['meta_title', 255],
            'meta description' => ['meta_description', 1000],
            'intro text' => ['intro_text', 5000],
        ];
    }
}
