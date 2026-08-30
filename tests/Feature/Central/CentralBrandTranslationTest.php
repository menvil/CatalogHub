<?php

declare(strict_types=1);

namespace Tests\Feature\Central;

use App\Enums\CentralBrandStatus;
use App\Enums\TranslationStatus;
use App\Enums\UserRole;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\Translations\BrandTranslation;
use App\Models\User;
use App\Queries\Translations\BrandTranslationEditorQuery;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Support\CountryReference;
use Tests\Support\DatabaseQueryCounter;
use Tests\TestCase;

final class CentralBrandTranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ca_015_routes_use_locale_codes_and_translation_permission(): void
    {
        $routes = [
            'central.brands.translations.index' => ['GET', 'admin/central/brands/{brand}/translations'],
            'central.brands.translations.edit' => ['GET', 'admin/central/brands/{brand}/translations/{locale}'],
            'central.brands.translations.save' => ['POST', 'admin/central/brands/{brand}/translations/{locale}'],
            'central.brands.translations.approve' => ['POST', 'admin/central/brands/{brand}/translations/{locale}/approve'],
            'central.brands.translations.outdated' => ['POST', 'admin/central/brands/{brand}/translations/{locale}/outdated'],
        ];

        foreach ($routes as $name => [$method, $uri]) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertContains($method, $route->methods());
            $this->assertSame($uri, $route->uri());
            $this->assertContains('can:translations.manage', $route->gatherMiddleware());
        }

        $brand = CentralBrand::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $this->assertStringEndsWith("/brands/{$brand->id}/translations/de-DE", route('central.brands.translations.edit', [$brand, $locale->code]));
    }

    public function test_translation_manager_can_open_active_or_archived_brand_but_catalog_only_user_cannot(): void
    {
        $translator = User::factory()->create(['role' => UserRole::Translator]);
        $catalogEditor = User::factory()->create();
        $active = CentralBrand::factory()->active()->create(['name' => 'Samsung']);
        $archived = CentralBrand::factory()->archived()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);

        $this->actingAs($translator)
            ->get(route('central.brands.translations.edit', [$active, $locale->code]))
            ->assertOk()
            ->assertSee('data-screen-id="CA-015"', false)
            ->assertSee('Samsung')
            ->assertSee('No translation row exists for this active locale. Nothing is persisted until Save.');
        $this->get(route('central.brands.translations.edit', [$archived, $locale->code]))->assertOk();

        $this->actingAs($catalogEditor)
            ->get(route('central.brands.translations.edit', [$active, $locale->code]))
            ->assertForbidden();
    }

    public function test_unknown_brand_unknown_locale_and_inactive_locale_are_not_available(): void
    {
        $user = User::factory()->create(['role' => UserRole::Translator]);
        $brand = CentralBrand::factory()->create();
        $inactive = Locale::factory()->disabled()->create(['code' => 'de-DE']);

        $this->actingAs($user)->get(route('central.brands.translations.index', 999999))->assertNotFound();
        $this->get("/admin/central/brands/{$brand->id}/translations/zz-ZZ")->assertNotFound();
        $this->get(route('central.brands.translations.edit', [$brand, $inactive->code]))->assertNotFound();
        $this->post(route('central.brands.translations.save', [$brand, $inactive->code]), ['name' => 'Nicht verfügbar'])->assertNotFound();
        $this->post(route('central.brands.translations.approve', [$brand, $inactive->code]))->assertNotFound();
        $this->post(route('central.brands.translations.outdated', [$brand, $inactive->code]))->assertNotFound();
        $this->assertDatabaseCount('brand_translations', 0);
    }

    public function test_index_selects_default_active_locale_then_position_and_handles_no_active_locales(): void
    {
        $user = User::factory()->create(['role' => UserRole::Translator]);
        $brand = CentralBrand::factory()->create();
        Locale::factory()->create(['code' => 'de-DE', 'position' => 0]);
        $default = Locale::factory()->create(['code' => 'en-US', 'is_default' => true, 'position' => 99]);

        $this->actingAs($user)
            ->get(route('central.brands.translations.index', $brand))
            ->assertRedirect(route('central.brands.translations.edit', [$brand, $default->code]));

        Locale::query()->update(['is_active' => false, 'is_default' => false]);

        $this->get(route('central.brands.translations.index', $brand))
            ->assertOk()
            ->assertSee('No active locales are available for translation.');
    }

    public function test_active_locale_selector_is_ordered_bounded_and_excludes_inactive_locales(): void
    {
        $brand = CentralBrand::factory()->create();
        $selected = Locale::factory()->create(['code' => 'de-DE', 'native_name' => 'Deutsch', 'position' => 2]);
        Locale::factory()->create(['code' => 'en-US', 'native_name' => 'English', 'is_default' => true, 'position' => 99]);
        Locale::factory()->create(['code' => 'fr-FR', 'native_name' => 'Français', 'position' => 1]);
        Locale::factory()->disabled()->create(['code' => 'es-ES', 'native_name' => 'Español']);

        $this->actingAs(User::factory()->create(['role' => UserRole::Translator]))
            ->get(route('central.brands.translations.edit', [$brand, $selected->code]))
            ->assertOk()
            ->assertSeeInOrder(['English', 'en-US', 'Français', 'fr-FR', 'Deutsch', 'de-DE'])
            ->assertDontSee('Español');

        $oneLocale = DatabaseQueryCounter::measure(fn () => app(BrandTranslationEditorQuery::class)->forBrand($brand, $selected));
        Locale::factory()->count(8)->create();
        $manyLocales = DatabaseQueryCounter::measure(fn () => app(BrandTranslationEditorQuery::class)->forBrand($brand, $selected));

        $this->assertSame($oneLocale['count'], $manyLocales['count']);
        $this->assertSame(3, $manyLocales['count']);
    }

    public function test_save_creates_translation_on_same_screen_without_mutating_canonical_brand(): void
    {
        $brand = CentralBrand::factory()->create([
            'name' => 'Samsung Electronics',
            'slug' => 'samsung',
            'status' => CentralBrandStatus::Archived,
            'website_url' => 'https://www.samsung.com',
            'country_id' => CountryReference::id('KR'),
        ]);
        $otherBrand = CentralBrand::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $otherLocale = Locale::factory()->create(['code' => 'fr-FR']);
        $attacker = User::factory()->create(['role' => UserRole::Translator]);

        $this->actingAs($attacker)
            ->post(route('central.brands.translations.save', [$brand, $locale->code]), [
                'name' => 'Samsung',
                'tagline' => 'Technologie für jeden',
                'short_description' => 'Kurze Beschreibung',
                'description' => 'Beschreibung',
                'seo_title' => 'Samsung Deutschland',
                'seo_description' => 'SEO Beschreibung',
                'status' => TranslationStatus::HumanReviewed->value,
                'brand_id' => $otherBrand->id,
                'locale_id' => $otherLocale->id,
                'locale' => $otherLocale->code,
                'source_hash' => str_repeat('f', 64),
                'approved_at' => '2020-01-01 00:00:00',
                'approved_by_user_id' => $attacker->id,
            ])
            ->assertRedirect(route('central.brands.translations.edit', [$brand, $locale->code]))
            ->assertSessionHas('success', 'Translation saved.');

        $translation = BrandTranslation::query()->sole();
        $this->assertSame($brand->id, $translation->brand_id);
        $this->assertSame($locale->id, $translation->locale_id);
        $this->assertSame('de-DE', $translation->locale);
        $this->assertNotSame(str_repeat('f', 64), $translation->source_hash);
        $this->assertNull($translation->approved_at);
        $this->assertNull($translation->approved_by_user_id);
        $this->assertDatabaseMissing('brand_translations', ['brand_id' => $otherBrand->id]);
        $this->assertDatabaseMissing('brand_translations', ['locale' => 'fr-FR']);

        $brand->refresh();
        $this->assertSame('Samsung Electronics', $brand->name);
        $this->assertSame('samsung', $brand->slug);
        $this->assertSame(CentralBrandStatus::Archived, $brand->status);
        $this->assertSame('https://www.samsung.com', $brand->website_url);
        $this->assertSame('KR', $brand->country()->first()?->alpha2);
    }

    public function test_validation_requires_name_rejects_direct_approval_and_preserves_old_input(): void
    {
        $brand = CentralBrand::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $route = route('central.brands.translations.edit', [$brand, $locale->code]);

        $this->actingAs(User::factory()->create(['role' => UserRole::Translator]))
            ->from($route)
            ->post(route('central.brands.translations.save', [$brand, $locale->code]), [
                'name' => '',
                'tagline' => 'Old input remains',
                'status' => TranslationStatus::Approved->value,
            ])
            ->assertRedirect($route)
            ->assertSessionHasErrors(['name', 'status'])
            ->assertSessionHasInput('tagline', 'Old input remains');

        $this->assertDatabaseCount('brand_translations', 0);
    }

    public function test_blank_optional_fields_clear_existing_values_to_null(): void
    {
        $brand = CentralBrand::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        BrandTranslation::factory()->create([
            'brand_id' => $brand->id,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
            'tagline' => 'Technology for everyone',
            'description' => 'Existing description',
            'seo_description' => 'Existing SEO description',
        ]);

        $this->actingAs(User::factory()->create(['role' => UserRole::Translator]))
            ->post(route('central.brands.translations.save', [$brand, $locale->code]), [
                'name' => 'Samsung',
                'tagline' => '',
                'description' => '   ',
                'seo_description' => '',
                'status' => TranslationStatus::HumanReviewed->value,
            ])
            ->assertRedirect();

        $translation = BrandTranslation::query()->sole();
        $this->assertNull($translation->tagline);
        $this->assertNull($translation->description);
        $this->assertNull($translation->seo_description);
    }

    public function test_approved_metadata_and_rtl_direction_are_presented_safely(): void
    {
        $brand = CentralBrand::factory()->create(['name' => 'Samsung']);
        $locale = Locale::factory()->create(['code' => 'ar-SA', 'native_name' => 'العربية', 'direction' => 'rtl']);
        $approver = User::factory()->centralAdmin()->create(['name' => 'Translation Approver', 'email' => 'approver@example.test']);
        BrandTranslation::factory()->create([
            'brand_id' => $brand->id,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
            'status' => TranslationStatus::Approved,
            'approved_at' => CarbonImmutable::parse('2026-08-24 12:30:00 UTC'),
            'approved_by_user_id' => $approver->id,
        ]);

        $this->actingAs(User::factory()->create(['role' => UserRole::Translator]))
            ->get(route('central.brands.translations.edit', [$brand, $locale->code]))
            ->assertOk()
            ->assertSee('Approved')
            ->assertSee('2026-08-24 12:30 UTC')
            ->assertSee('Translation Approver')
            ->assertSee('approver@example.test')
            ->assertSee('id="name" name="name" type="text"', false)
            ->assertSee('dir="rtl"', false)
            ->assertDontSee('approved_by_user_id');
    }

    public function test_brand_navigation_hides_links_that_the_current_role_cannot_open(): void
    {
        $brand = CentralBrand::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);

        $this->actingAs(User::factory()->create())
            ->get(route('central.brands.show', $brand))
            ->assertOk()
            ->assertSee('Overview')
            ->assertSee('Media')
            ->assertDontSee('central.brands.translations.index')
            ->assertDontSee('>Translations<', false);

        $this->actingAs(User::factory()->create(['role' => UserRole::Translator]))
            ->get(route('central.brands.translations.edit', [$brand, $locale->code]))
            ->assertOk()
            ->assertSee('Translations')
            ->assertDontSee('href="'.route('central.brands.show', $brand, absolute: false).'"', false)
            ->assertDontSee('href="'.route('central.brands.media', $brand, absolute: false).'"', false);
    }
}
