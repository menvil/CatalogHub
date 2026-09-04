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
use App\Services\Translations\TranslationSourceHashService;
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
            ->assertDontSee('Review translations')
            ->assertSee('href="'.route('central.brands.edit', $brand, absolute: false).'"', false);
    }

    public function test_translation_summary_counts_each_active_locale_once_without_persisted_counters(): void
    {
        $brand = CentralBrand::factory()->active()->create(['name' => 'Mixed Translation Brand']);
        $locales = collect([
            ['en-US', 'English'],
            ['de-DE', 'German'],
            ['fr-FR', 'French'],
            ['bg-BG', 'Bulgarian'],
            ['ms-MY', 'Malay'],
        ])->map(fn (array $locale): Locale => Locale::factory()->create([
            'code' => $locale[0],
            'name' => $locale[1],
            'is_active' => true,
        ]));
        $statuses = [
            TranslationStatus::Approved,
            TranslationStatus::HumanReviewed,
            TranslationStatus::MachineTranslated,
            TranslationStatus::Outdated,
        ];

        foreach ($statuses as $index => $status) {
            BrandTranslation::factory()->create([
                'brand_id' => $brand->id,
                'locale_id' => $locales[$index]->id,
                'locale' => $locales[$index]->code,
                'status' => $status,
            ]);
        }

        $response = $this->actingAs(User::factory()->centralAdmin()->create())
            ->get(route('central.brands.show', $brand))
            ->assertOk()
            ->assertSee('data-screen-region="translation-summary"', false)
            ->assertSee('3 of 5 active locales complete')
            ->assertSee('Approved 1')
            ->assertSee('Reviewed 1')
            ->assertSee('Machine 1')
            ->assertSee('Missing 1')
            ->assertSee('Outdated 1')
            ->assertSee('Review translations');

        $summary = $response->viewData('translationSummary');
        self::assertSame(5, $summary->total);
        self::assertSame(3, $summary->complete());
        self::assertSame(60, $summary->score());
        self::assertFalse(Schema::hasColumn('central_brands', 'translation_count'));
        self::assertFalse(Schema::hasColumn('central_brands', 'translation_coverage'));
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

    public function test_http_translation_workflow_immediately_removes_and_restores_derived_quality_issues(): void
    {
        $brand = CentralBrand::factory()->active()->create(['name' => 'Workflow Brand']);
        $locale = Locale::factory()->create(['code' => 'de-DE', 'name' => 'German']);
        $actor = User::factory()->centralAdmin()->create();
        $save = route('central.brands.translations.save', [$brand, $locale->code]);
        $payload = [
            'name' => 'Workflow Marke',
            'tagline' => 'Beständige Übersetzung',
            'status' => TranslationStatus::HumanReviewed->value,
        ];

        $this->actingAs($actor);
        self::assertContains('brand_translation_missing', $this->get(route('central.brands.show', $brand))->viewData('quality')->issueCodes());

        $this->post($save, $payload)->assertRedirect();
        self::assertNotContains('brand_translation_missing', $this->get(route('central.brands.show', $brand))->viewData('quality')->issueCodes());

        $this->post(route('central.brands.translations.approve', [$brand, $locale->code]))->assertRedirect();
        $translation = BrandTranslation::query()->sole();
        self::assertSame(TranslationStatus::Approved, $translation->status);
        self::assertSame(app(TranslationSourceHashService::class)->forBrand($brand), $translation->source_hash);

        $this->post(route('central.brands.translations.outdated', [$brand, $locale->code]))->assertRedirect();
        $outdatedQuality = $this->get(route('central.brands.show', $brand))->viewData('quality');
        self::assertContains('brand_translation_outdated', $outdatedQuality->issueCodes());
        self::assertSame('Beständige Übersetzung', $translation->fresh()->tagline);

        $this->post($save, [...$payload, 'tagline' => 'Korrigierte Übersetzung'])->assertRedirect();
        $reviewedQuality = $this->get(route('central.brands.show', $brand))->viewData('quality');
        self::assertNotContains('brand_translation_outdated', $reviewedQuality->issueCodes());
        self::assertSame(TranslationStatus::HumanReviewed, $translation->fresh()->status);
        self::assertSame(CentralBrandStatus::Active, $brand->fresh()->status);
    }
}
