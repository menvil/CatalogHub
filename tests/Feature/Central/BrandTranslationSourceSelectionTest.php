<?php

declare(strict_types=1);

namespace Tests\Feature\Central;

use App\Enums\TranslationStatus;
use App\Enums\UserRole;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\Translations\BrandTranslation;
use App\Models\User;
use App\Queries\Translations\BrandTranslationEditorQuery;
use App\Services\Translations\TranslationSourceHashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\DatabaseQueryCounter;
use Tests\TestCase;

final class BrandTranslationSourceSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_prefers_configured_usable_locale_then_quality_then_stable_locale_order(): void
    {
        $brand = CentralBrand::factory()->create();
        $target = Locale::factory()->create(['code' => 'de-DE']);
        $default = Locale::factory()->create(['code' => 'bg-BG', 'is_default' => true]);
        $approved = Locale::factory()->create(['code' => 'en-US', 'position' => 0]);
        $reviewed = Locale::factory()->create(['code' => 'fr-FR', 'position' => 1]);
        $machine = Locale::factory()->create(['code' => 'es-ES', 'position' => 2]);
        $this->row($brand, $default, TranslationStatus::MachineTranslated);
        $approvedRow = $this->row($brand, $approved, TranslationStatus::Approved);
        $reviewedRow = $this->row($brand, $reviewed, TranslationStatus::HumanReviewed);
        $machineRow = $this->row($brand, $machine, TranslationStatus::MachineTranslated);
        $query = app(BrandTranslationEditorQuery::class);

        $selection = $query->forBrand($brand, $target);
        $this->assertSame($default->id, $selection->sourceLocale?->getKey());
        BrandTranslation::query()->where('locale_id', $default->id)->delete();
        $selection = $query->forBrand($brand, $target);
        $this->assertSame($approved->id, $selection->sourceLocale?->getKey());
        $approvedRow->update(['status' => TranslationStatus::Outdated]);
        $selection = $query->forBrand($brand, $target);
        $this->assertSame($reviewed->id, $selection->sourceLocale?->getKey());
        $reviewedRow->update(['status' => TranslationStatus::Outdated]);
        $selection = $query->forBrand($brand, $target);
        $this->assertSame($machine->id, $selection->sourceLocale?->getKey());
        $machineRow->delete();
        $selection = $query->forBrand($brand, $target);
        $this->assertSame($approved->id, $selection->sourceLocale?->getKey());
        $this->assertNull($query->forBrand($brand, $approved)->sourceLocales->firstWhere('code', 'en-US'));
    }

    public function test_explicit_source_renders_every_localized_field_without_writes_or_hash_changes(): void
    {
        $brand = CentralBrand::factory()->create(['name' => 'Canonical identity']);
        $source = Locale::factory()->create(['code' => 'en-US', 'name' => 'English']);
        $target = Locale::factory()->create(['code' => 'ar-SA', 'name' => 'Arabic', 'direction' => 'rtl']);
        $row = $this->row($brand, $source, TranslationStatus::Approved);
        $fields = ['name', 'tagline', 'short_description', 'description', 'seo_title', 'seo_description'];
        $values = array_combine($fields, array_map(fn (string $field): string => "Source {$field}\nSecond paragraph <script>alert(1)</script>", $fields));
        $row->forceFill($values)->saveOrFail();
        $before = $row->fresh()?->getRawOriginal();
        $auditCount = AuditLogEntry::query()->count();
        $brandBefore = $brand->fresh()?->getRawOriginal();
        $this->actingAs(User::factory()->create(['role' => UserRole::Translator]));
        $response = $this->get(route('central.brands.translations.edit', [$brand, $target->code, 'source' => $source->code]));
        $response->assertOk()->assertSee('English (en-US) → Arabic (ar-SA)')
            ->assertSee('dir="rtl"', false)->assertSee('dir="ltr"', false)
            ->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
        foreach ($fields as $field) {
            $response->assertSee('data-translation-field="'.$field.'"', false)
                ->assertSee('data-source-field="'.$field.'"', false)->assertSee('Source '.$field);
        }
        $this->assertSame($before, $row->fresh()?->getRawOriginal());
        $this->assertSame($brandBefore, $brand->fresh()?->getRawOriginal());
        $this->assertDatabaseCount('brand_translations', 1);
        $this->assertSame($auditCount, AuditLogEntry::query()->count());
        $this->assertNull(app(BrandTranslationEditorQuery::class)->forBrand($brand, $target, 'en-US')->translation);
    }

    public function test_missing_and_outdated_sources_are_explicit_and_do_not_block_target_editor(): void
    {
        $brand = CentralBrand::factory()->create();
        $target = Locale::factory()->create(['code' => 'de-DE']);
        $source = Locale::factory()->create(['code' => 'en-US', 'name' => 'English']);
        $url = route('central.brands.translations.edit', [$brand, $target->code, 'source' => $source->code]);
        $this->actingAs(User::factory()->create(['role' => UserRole::Translator]))->get($url)
            ->assertOk()->assertSee('No source translation available for English (en-US).')
            ->assertSee('No source value')->assertDontSee('data-brand-translation-copy-all', false);
        $this->assertDatabaseCount('brand_translations', 0);
        $row = $this->row($brand, $source, TranslationStatus::Outdated);
        $row->forceFill(['tagline' => null, 'name' => ''])->saveOrFail();
        $this->get($url)->assertOk()->assertSee('You are translating from an outdated source.')
            ->assertSee('Canonical fallback')->assertSee('No source value')
            ->assertDontSee('data-brand-translation-copy-target="tagline"', false);
        $this->assertDatabaseCount('brand_translations', 1);
    }

    public function test_same_unknown_inactive_and_array_sources_are_rejected_before_any_mutation(): void
    {
        $brand = CentralBrand::factory()->create();
        $target = Locale::factory()->create(['code' => 'de-DE']);
        Locale::factory()->disabled()->create(['code' => 'en-US']);
        $auditCount = AuditLogEntry::query()->count();
        $this->actingAs(User::factory()->create(['role' => UserRole::Translator]));
        foreach (['de-DE', 'en-US', 'zz-ZZ', ['en-US']] as $source) {
            $parameters = [$brand, $target->code, 'source' => $source];
            $this->getJson(route('central.brands.translations.edit', $parameters))->assertRedirect()->assertSessionHasErrors('source');
            foreach (['save', 'approve', 'outdated'] as $action) {
                $this->postJson(route('central.brands.translations.'.$action, $parameters), ['name' => 'Target'])->assertRedirect()->assertSessionHasErrors('source');
            }
        }
        $this->assertDatabaseCount('brand_translations', 0);
        $this->assertSame($auditCount, AuditLogEntry::query()->count());
    }

    public function test_save_and_workflow_redirects_keep_source_and_never_mutate_it(): void
    {
        $brand = CentralBrand::factory()->create();
        $source = Locale::factory()->create(['code' => 'en-US']);
        $target = Locale::factory()->create(['code' => 'de-DE']);
        $row = $this->row($brand, $source, TranslationStatus::Outdated);
        $before = $row->fresh()?->getRawOriginal();
        $parameters = [$brand, $target->code, 'source' => 'en-US'];
        $edit = route('central.brands.translations.edit', $parameters);
        $this->actingAs(User::factory()->create(['role' => UserRole::Translator]))
            ->from($edit)->post(route('central.brands.translations.save', $parameters), ['name' => '', 'tagline' => 'Keep draft'])
            ->assertRedirect($edit)->assertSessionHasErrors('name')->assertSessionHasInput('tagline', 'Keep draft');
        $this->post(route('central.brands.translations.save', $parameters), ['name' => 'Target'])
            ->assertRedirect($edit);
        $saved = BrandTranslation::query()->where('locale_id', $target->id)->sole();
        $this->assertSame(TranslationStatus::HumanReviewed, $saved->status);
        $this->assertSame(app(TranslationSourceHashService::class)->forBrand($brand), $saved->source_hash);
        $this->post(route('central.brands.translations.approve', $parameters))->assertRedirect($edit);
        $this->post(route('central.brands.translations.outdated', $parameters))->assertRedirect($edit);
        $this->assertSame($before, $row->fresh()?->getRawOriginal());
    }

    public function test_source_navigation_and_bulk_read_stay_bounded_with_many_translations(): void
    {
        $brand = CentralBrand::factory()->create();
        $source = Locale::factory()->create(['code' => 'en-US']);
        $target = Locale::factory()->create(['code' => 'de-DE']);
        $this->row($brand, $source, TranslationStatus::HumanReviewed);
        $query = app(BrandTranslationEditorQuery::class);
        $small = DatabaseQueryCounter::measure(fn () => $query->forBrand($brand, $target, 'en-US'));
        foreach (Locale::factory()->count(12)->create() as $locale) {
            $this->row($brand, $locale, TranslationStatus::HumanReviewed);
        }
        $large = DatabaseQueryCounter::measure(fn () => $query->forBrand($brand, $target, 'en-US'));
        $this->assertSame($small['count'], $large['count']);
        $this->actingAs(User::factory()->create(['role' => UserRole::Translator]))
            ->get(route('central.brands.translations.edit', [$brand, $target->code, 'source' => 'en-US']))
            ->assertOk()->assertSee('/translations/de-DE?source=en-US', false)
            ->assertDontSee('/translations/en-US?source=en-US', false);
        $this->assertTrue($query->forBrand($brand, $source)->sourceLocale?->is($source) !== true);
    }

    public function test_mutations_validate_source_without_loading_editor_locales_translations_or_activity(): void
    {
        $brand = CentralBrand::factory()->create();
        $source = Locale::factory()->create(['code' => 'en-US']);
        $target = Locale::factory()->create(['code' => 'de-DE']);
        $this->row($brand, $source, TranslationStatus::Approved);
        $this->actingAs(User::factory()->create(['role' => UserRole::Translator]));
        foreach (['save', 'approve', 'outdated'] as $action) {
            DatabaseQueryCounter::measure(fn () => $this->post(
                route('central.brands.translations.'.$action, [$brand, $target->code, 'source' => $source->code]),
                $action === 'save' ? ['name' => 'Target'] : [],
            )->assertRedirect());
            foreach (DB::getQueryLog() as $query) {
                $sql = strtolower($query['query']);
                $this->assertDoesNotMatchRegularExpression('/from ["`]?locales["`]?.*order by/i', $sql);
                $this->assertDoesNotMatchRegularExpression('/from ["`]?brand_translations["`]?.*locale_id["`]? in/i', $sql);
                $this->assertDoesNotMatchRegularExpression('/^select .*from ["`]?audit_log_entries/i', $sql);
            }
        }
    }

    public function test_initial_counter_matches_native_utf16_length_for_saved_supplementary_characters(): void
    {
        $brand = CentralBrand::factory()->create();
        $target = Locale::factory()->create(['code' => 'de-DE']);
        $this->row($brand, $target, TranslationStatus::HumanReviewed)->update(['tagline' => 'A😀B']);
        $response = $this->actingAs(User::factory()->create(['role' => UserRole::Translator]))
            ->get(route('central.brands.translations.edit', [$brand, $target->code]))->assertOk();
        $response->assertSee('data-brand-translation-counter="tagline">4 / 255', false);
    }

    public function test_authorized_read_only_viewer_sees_content_and_activity_without_mutation_actions(): void
    {
        config(['cataloghub_permissions.roles.translator' => [
            'central.panel.access', 'central.page.access', 'central.view', 'translations.manage',
        ]]);
        $brand = CentralBrand::factory()->create();
        $source = Locale::factory()->create(['code' => 'en-US']);
        $target = Locale::factory()->create(['code' => 'de-DE']);
        $this->row($brand, $source, TranslationStatus::Approved);
        $this->actingAs(User::factory()->create(['role' => UserRole::Translator]))
            ->get(route('central.brands.translations.edit', [$brand, $target->code]))
            ->assertOk()->assertSee('Workflow status')->assertSee('Recent activity')
            ->assertSee('readonly', false)->assertDontSee('Copy source')->assertDontSee('Copy all from Source')
            ->assertDontSee('Save translation')->assertDontSee('Approve translation')->assertDontSee('Mark outdated');
        foreach (['save', 'approve', 'outdated'] as $action) {
            $this->post(route('central.brands.translations.'.$action, [$brand, $target->code]), ['name' => 'Target'])->assertForbidden();
        }
        $this->assertDatabaseCount('brand_translations', 1);
    }

    public function test_language_menus_offer_active_locales_and_valid_swap_urls_without_a_locale_strip(): void
    {
        $brand = CentralBrand::factory()->create();
        $source = Locale::factory()->create(['code' => 'en-US', 'name' => 'English']);
        $target = Locale::factory()->create(['code' => 'de-DE', 'name' => 'German']);
        Locale::factory()->disabled()->create(['code' => 'xx-XX', 'name' => 'Inactive language']);
        $this->row($brand, $source, TranslationStatus::Approved);
        $this->row($brand, $target, TranslationStatus::Outdated);
        $url = route('central.brands.translations.edit', [$brand, $target->code, 'source' => $source->code]);
        $swap = route('central.brands.translations.edit', [$brand, $source->code, 'source' => $target->code], absolute: false);
        $response = $this->actingAs(User::factory()->create(['role' => UserRole::Translator]))->get($url);
        $response->assertOk()->assertSee('id="source-language"', false)->assertSee('id="target-language"', false)
            ->assertSee('value="de-DE" data-language-url="'.$swap.'"', false)
            ->assertSee('value="en-US" data-language-url="'.$swap.'"', false)
            ->assertSee('English · en-US · Approved')->assertSee('German · de-DE · Outdated')
            ->assertDontSee('Inactive language')->assertDontSee('brand-translation-locales', false)
            ->assertDontSee('/translations/en-US?source=en-US', false)->assertDontSee('/translations/de-DE?source=de-DE', false);
        $this->get($swap)->assertOk()->assertSee('German (de-DE) → English (en-US)');
        $this->assertDatabaseCount('brand_translations', 2);
    }

    public function test_single_active_locale_keeps_target_visible_without_offering_self_source(): void
    {
        $brand = CentralBrand::factory()->create();
        $target = Locale::factory()->create(['code' => 'en-US', 'name' => 'English']);
        $response = $this->actingAs(User::factory()->create(['role' => UserRole::Translator]))
            ->get(route('central.brands.translations.edit', [$brand, $target->code]));
        $response->assertOk()->assertSee('id="target-language"', false)->assertSee('Choose source language')
            ->assertDontSee('Switch direction')->assertDontSee('/translations/en-US?source=en-US', false);
        $this->assertDatabaseCount('brand_translations', 0);
    }

    private function row(CentralBrand $brand, Locale $locale, TranslationStatus $status): BrandTranslation
    {
        return BrandTranslation::factory()->create([
            'brand_id' => $brand->id, 'locale_id' => $locale->id, 'locale' => $locale->code,
            'status' => $status, 'source_hash' => app(TranslationSourceHashService::class)->forBrand($brand),
        ]);
    }
}
