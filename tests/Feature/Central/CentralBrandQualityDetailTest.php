<?php

declare(strict_types=1);

namespace Tests\Feature\Central;

use App\Enums\CentralBrandQualityState;
use App\Enums\CentralBrandStatus;
use App\Enums\TranslationStatus;
use App\Enums\UserRole;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\Translations\BrandTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CentralBrandQualityDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_ca_012_renders_real_derived_quality_and_profile_media_translation_destinations(): void
    {
        Storage::fake('public');
        $brand = CentralBrand::factory()->active()->create(['name' => 'Needs Attention']);
        $locale = Locale::factory()->create(['code' => 'de-DE', 'name' => 'German', 'native_name' => 'Deutsch']);

        $response = $this->actingAs(User::factory()->centralAdmin()->create())
            ->get(route('central.brands.show', $brand))
            ->assertOk()
            ->assertSee('data-screen-region="quality-completeness"', false)
            ->assertSee('Needs attention')
            ->assertSee('0%')
            ->assertSee('0 of 7 checks complete')
            ->assertSee('Country is missing')
            ->assertSee('Primary Brand logo is missing')
            ->assertSee('German (de-DE) translation is missing')
            ->assertSee('href="'.route('central.brands.edit', $brand, absolute: false).'"', false)
            ->assertSee('href="'.route('central.brands.media', $brand, absolute: false).'"', false)
            ->assertSee('href="'.route('central.brands.translations.edit', [$brand, $locale->code], absolute: false).'"', false);

        $quality = $response->viewData('quality');
        self::assertSame(CentralBrandQualityState::NeedsAttention, $quality->state);
        self::assertSame(0, $quality->score);
    }

    public function test_underlying_profile_and_translation_changes_immediately_change_the_summary(): void
    {
        Storage::fake('public');
        $brand = CentralBrand::factory()->active()->create(['name' => 'Immediate Brand']);
        $locale = Locale::factory()->create(['code' => 'de-DE', 'name' => 'German']);
        $user = User::factory()->centralAdmin()->create();

        $before = $this->actingAs($user)->get(route('central.brands.show', $brand))->viewData('quality');

        $brand->forceFill(['website_url' => 'https://example.test'])->saveOrFail();
        BrandTranslation::factory()->create([
            'brand_id' => $brand->id,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
            'status' => TranslationStatus::HumanReviewed,
        ]);
        $after = $this->get(route('central.brands.show', $brand))->viewData('quality');

        self::assertSame(0, $before->score);
        self::assertSame(29, $after->score);
        self::assertContains('brand_translation_missing', $before->issueCodes());
        self::assertNotContains('brand_translation_missing', $after->issueCodes());
        self::assertNotContains('brand_website_missing', $after->issueCodes());
    }

    public function test_translation_cta_is_hidden_without_translation_permission_while_issue_remains_readable(): void
    {
        $brand = CentralBrand::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE', 'name' => 'German']);

        $this->actingAs(User::factory()->create(['role' => UserRole::CatalogEditor]))
            ->get(route('central.brands.show', $brand))
            ->assertOk()
            ->assertSee('German (de-DE) translation is missing')
            ->assertDontSee('href="'.route('central.brands.translations.edit', [$brand, $locale->code], absolute: false).'"', false)
            ->assertSee('href="'.route('central.brands.edit', $brand, absolute: false).'"', false);
    }

    public function test_archived_brand_quality_renders_without_changing_lifecycle(): void
    {
        $brand = CentralBrand::factory()->archived()->create(['name' => 'Archived Quality Brand']);

        $this->actingAs(User::factory()->create())
            ->get(route('central.brands.show', $brand))
            ->assertOk()
            ->assertSee('Needs attention')
            ->assertSee('Archived')
            ->assertSee('Restore Brand');

        self::assertSame(CentralBrandStatus::Archived, $brand->fresh()->status);
    }

    public function test_quality_state_is_not_persisted_on_central_brands(): void
    {
        foreach (['quality_status', 'quality_score', 'completeness', 'is_complete', 'has_quality_issues', 'quality_issues', 'quality_calculated_at'] as $column) {
            self::assertFalse(Schema::hasColumn('central_brands', $column), $column.' must remain derived.');
        }
    }
}
