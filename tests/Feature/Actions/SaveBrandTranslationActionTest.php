<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\Translations\SaveBrandTranslationAction;
use App\Data\Translations\BrandTranslationInput;
use App\Enums\AuditAction;
use App\Enums\TranslationStatus;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\Translations\BrandTranslation;
use App\Models\User;
use App\Queries\Translations\BrandTranslationEditorQuery;
use App\Services\Translations\TranslationSourceHashService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class SaveBrandTranslationActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_and_updates_one_server_bound_translation_with_current_source_hash(): void
    {
        $brand = CentralBrand::factory()->create(['name' => 'Samsung Electronics', 'slug' => 'samsung']);
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $action = app(SaveBrandTranslationAction::class);

        $created = $action->handle(User::factory()->create(), $brand, $locale, $this->input(
            name: 'Samsung',
            tagline: 'Technologie für jeden',
        ));

        $this->assertSame($brand->id, $created->brand_id);
        $this->assertSame($locale->id, $created->locale_id);
        $this->assertSame('de-DE', $created->locale);
        $this->assertSame(TranslationStatus::HumanReviewed, $created->status);
        $this->assertSame(app(TranslationSourceHashService::class)->forBrand($brand), $created->source_hash);

        $brand->update(['name' => 'Samsung Electronics Co.']);
        $updated = $action->handle(User::factory()->create(), $brand, $locale, $this->input(
            name: 'Samsung Deutschland',
            tagline: null,
            seoDescription: null,
            status: TranslationStatus::MachineTranslated,
        ));

        $this->assertTrue($created->is($updated));
        $this->assertSame('Samsung Deutschland', $updated->name);
        $this->assertNull($updated->tagline);
        $this->assertNull($updated->seo_description);
        $this->assertSame(TranslationStatus::MachineTranslated, $updated->status);
        $this->assertSame(app(TranslationSourceHashService::class)->forBrand($brand), $updated->source_hash);
        $this->assertSame(1, BrandTranslation::query()->count());
    }

    public function test_source_hash_uses_the_locked_current_brand_instead_of_the_stale_argument(): void
    {
        $staleBrand = CentralBrand::factory()->create(['name' => 'Samsung', 'slug' => 'samsung']);
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $hashService = app(TranslationSourceHashService::class);

        CentralBrand::query()->findOrFail($staleBrand->id)->update([
            'name' => 'Samsung Electronics',
            'slug' => 'samsung-electronics',
        ]);
        $currentBrand = $staleBrand->fresh();

        $this->assertNotSame($hashService->forBrand($staleBrand), $hashService->forBrand($currentBrand));

        $saved = app(SaveBrandTranslationAction::class)->handle(
            User::factory()->create(),
            $staleBrand,
            $locale,
            $this->input(),
        );

        $this->assertSame($hashService->forBrand($staleBrand->fresh()), $saved->source_hash);
    }

    public function test_save_invalidates_the_shared_translation_dashboard_cache(): void
    {
        Cache::put('translations.dashboard.stats', ['stale' => true], 60);

        app(SaveBrandTranslationAction::class)->handle(User::factory()->create(),
            CentralBrand::factory()->create(),
            Locale::factory()->create(),
            $this->input(),
        );

        $this->assertFalse(Cache::has('translations.dashboard.stats'));
    }

    public function test_locale_code_rename_updates_the_existing_locale_identity(): void
    {
        $brand = CentralBrand::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $action = app(SaveBrandTranslationAction::class);
        $created = $action->handle(User::factory()->create(), $brand, $locale, $this->input(name: 'Samsung Deutschland'));

        $locale->update(['code' => 'de-AT']);
        $locale->refresh();

        $editor = app(BrandTranslationEditorQuery::class)->forBrand($brand, $locale);
        $this->assertTrue($created->is($editor->translation));

        $updated = $action->handle(User::factory()->create(), $brand, $locale, $this->input(name: 'Samsung Österreich'));

        $this->assertTrue($created->is($updated));
        $this->assertSame($locale->id, $updated->locale_id);
        $this->assertSame('de-AT', $updated->locale);
        $this->assertSame('Samsung Österreich', $updated->name);
        $this->assertSame(1, BrandTranslation::query()->count());
    }

    public function test_true_no_op_does_not_touch_the_row_or_create_audit_noise(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $action = app(SaveBrandTranslationAction::class);
        $created = $action->handle($actor, $brand, $locale, $this->input());
        $createdAt = $created->updated_at;

        $saved = $action->handle($actor, $brand, $locale, $this->input());

        $this->assertTrue($created->is($saved));
        $this->assertTrue($createdAt?->equalTo($saved->updated_at));
        $this->assertSame(1, $this->savedAuditQuery($brand)->count());
    }

    public function test_editing_approved_copy_invalidates_approval_without_stale_attribution(): void
    {
        $approver = User::factory()->create();
        $editor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $translation = BrandTranslation::factory()->create([
            'brand_id' => $brand->id,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
            'name' => 'Samsung',
            'tagline' => 'Technology for everyone',
            'short_description' => 'Localized summary',
            'description' => 'Localized description',
            'seo_title' => 'Samsung localized',
            'seo_description' => 'Samsung products',
            'status' => TranslationStatus::Approved,
            'source_hash' => app(TranslationSourceHashService::class)->forBrand($brand),
            'approved_at' => now(),
            'approved_by_user_id' => $approver->id,
        ]);

        $updated = app(SaveBrandTranslationAction::class)->handle(
            $editor,
            $brand,
            $locale,
            $this->input(name: 'Samsung Deutschland', status: TranslationStatus::Approved),
        );

        $this->assertTrue($translation->is($updated));
        $this->assertSame(TranslationStatus::HumanReviewed, $updated->status);
        $this->assertNull($updated->approved_at);
        $this->assertNull($updated->approved_by_user_id);
        $this->assertContains('status', AuditLogEntry::query()->latest('id')->firstOrFail()->after_json['changed_fields']);
    }

    public function test_unchanged_approved_save_preserves_approval_but_direct_approval_is_rejected(): void
    {
        $approver = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $approvedAt = now()->subDay();
        BrandTranslation::factory()->create([
            'brand_id' => $brand->id,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
            'name' => 'Samsung',
            'tagline' => 'Technology for everyone',
            'short_description' => 'Localized summary',
            'description' => 'Localized description',
            'seo_title' => 'Samsung localized',
            'seo_description' => 'Samsung products',
            'status' => TranslationStatus::Approved,
            'source_hash' => app(TranslationSourceHashService::class)->forBrand($brand),
            'approved_at' => $approvedAt,
            'approved_by_user_id' => $approver->id,
        ]);

        $saved = app(SaveBrandTranslationAction::class)->handle(User::factory()->create(), $brand, $locale, $this->input(status: TranslationStatus::Approved));
        $this->assertSame(TranslationStatus::Approved, $saved->status);
        $this->assertSame($approver->id, $saved->approved_by_user_id);
        $this->assertSame($approvedAt->format('Y-m-d H:i:s'), $saved->getRawOriginal('approved_at'));
        $this->assertSame(0, $this->savedAuditQuery($brand)->count());

        $newLocale = Locale::factory()->create(['code' => 'fr-FR']);
        $this->expectException(ValidationException::class);
        app(SaveBrandTranslationAction::class)->handle(User::factory()->create(), $brand, $newLocale, $this->input(status: TranslationStatus::Approved));
    }

    private function input(
        string $name = 'Samsung',
        ?string $tagline = 'Technology for everyone',
        ?string $seoDescription = 'Samsung products',
        TranslationStatus $status = TranslationStatus::HumanReviewed,
    ): BrandTranslationInput {
        return new BrandTranslationInput(
            name: $name,
            tagline: $tagline,
            shortDescription: 'Localized summary',
            description: 'Localized description',
            seoTitle: 'Samsung localized',
            seoDescription: $seoDescription,
            status: $status,
        );
    }

    /** @return Builder<AuditLogEntry> */
    private function savedAuditQuery(CentralBrand $brand): Builder
    {
        return AuditLogEntry::query()
            ->where('action', AuditAction::CatalogBrandTranslationSaved->value)
            ->where('subject_type', $brand->getMorphClass())
            ->where('subject_id', (string) $brand->getKey());
    }
}
