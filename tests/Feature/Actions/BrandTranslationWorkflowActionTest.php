<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\Translations\ApproveBrandTranslationAction;
use App\Actions\Translations\MarkBrandTranslationOutdatedAction;
use App\Enums\AuditAction;
use App\Enums\CentralBrandStatus;
use App\Enums\TranslationStatus;
use App\Enums\UserRole;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\Translations\BrandTranslation;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Translations\TranslationSourceHashService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

final class BrandTranslationWorkflowActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_explicit_approval_is_server_owned_minimized_and_does_not_change_brand_lifecycle(): void
    {
        CarbonImmutable::setTestNow('2026-08-28 10:15:00 UTC');
        $actor = User::factory()->create(['role' => UserRole::Translator]);
        $brand = CentralBrand::factory()->archived()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $translation = $this->translation($brand, $locale, TranslationStatus::HumanReviewed);

        $approved = app(ApproveBrandTranslationAction::class)->handle($actor, $brand, $locale);

        $this->assertSame(TranslationStatus::Approved, $approved->status);
        $this->assertSame($actor->id, $approved->approved_by_user_id);
        $this->assertSame('2026-08-28 10:15:00', $approved->getRawOriginal('approved_at'));
        $this->assertSame(CentralBrandStatus::Archived, $brand->fresh()->status);

        $entry = $this->workflowAuditQuery($brand, AuditAction::TranslationApproved)->sole();
        $this->assertSame(AuditAction::TranslationApproved->value, $entry->action);
        $this->assertSame($actor->id, $entry->actor_id);
        $this->assertSame($brand->getMorphClass(), $entry->subject_type);
        $this->assertSame((string) $brand->id, $entry->subject_id);
        $this->assertSame($translation->id, $entry->after_json['translation_id']);
        $this->assertSame('de-DE', $entry->after_json['locale']);
        $this->assertSame('approved', $entry->after_json['status']);
        $this->assertSame(['status', 'approval'], $entry->after_json['changed_fields']);
        $this->assertStringNotContainsString($translation->name, json_encode([$entry->before_json, $entry->after_json], JSON_THROW_ON_ERROR));
    }

    public function test_approval_rejects_ineligible_or_stale_rows_and_existing_approval_is_a_no_op(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $machineLocale = Locale::factory()->create(['code' => 'de-DE']);
        $staleLocale = Locale::factory()->create(['code' => 'fr-FR']);
        $approvedLocale = Locale::factory()->create(['code' => 'it-IT']);
        $this->translation($brand, $machineLocale, TranslationStatus::MachineTranslated);
        $this->translation($brand, $staleLocale, TranslationStatus::HumanReviewed, ['source_hash' => str_repeat('0', 64)]);
        $alreadyApproved = $this->translation($brand, $approvedLocale, TranslationStatus::Approved, [
            'approved_at' => CarbonImmutable::parse('2026-08-20 09:00:00 UTC'),
            'approved_by_user_id' => $actor->id,
        ]);

        foreach ([$machineLocale, $staleLocale] as $locale) {
            try {
                app(ApproveBrandTranslationAction::class)->handle($actor, $brand, $locale);
                $this->fail('Expected approval validation failure.');
            } catch (ValidationException) {
                $this->assertNotSame(TranslationStatus::Approved, BrandTranslation::query()->where('locale_id', $locale->id)->sole()->status);
            }
        }

        $result = app(ApproveBrandTranslationAction::class)->handle($actor, $brand, $approvedLocale);
        $this->assertTrue($alreadyApproved->is($result));
        $this->assertSame('2026-08-20 09:00:00', $result->getRawOriginal('approved_at'));
        $this->assertSame(0, $this->workflowAuditQuery($brand)->count());
    }

    public function test_approval_forgets_dashboard_cache_after_the_transaction_succeeds(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $this->translation($brand, $locale, TranslationStatus::HumanReviewed);
        Cache::put('translations.dashboard.stats', ['stale' => true], 60);

        app(ApproveBrandTranslationAction::class)->handle($actor, $brand, $locale);

        $this->assertFalse(Cache::has('translations.dashboard.stats'));
    }

    public function test_approval_keeps_dashboard_cache_when_the_transaction_rolls_back(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $translation = $this->translation($brand, $locale, TranslationStatus::HumanReviewed);
        Cache::put('translations.dashboard.stats', ['stale' => true], 60);
        $audit = $this->createMock(AuditRecorder::class);
        $audit->method('record')->willThrowException(new RuntimeException('audit unavailable'));
        $this->app->instance(AuditRecorder::class, $audit);

        try {
            app(ApproveBrandTranslationAction::class)->handle($actor, $brand, $locale);
            $this->fail('Expected audit failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('audit unavailable', $exception->getMessage());
        }

        $this->assertTrue(Cache::has('translations.dashboard.stats'));
        $this->assertSame(TranslationStatus::HumanReviewed, $translation->fresh()->status);
    }

    public function test_mark_outdated_preserves_copy_clears_approval_and_is_no_op_when_already_outdated(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->active()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $translation = $this->translation($brand, $locale, TranslationStatus::Approved, [
            'name' => 'Privater Markenname',
            'tagline' => 'Private Tagline',
            'description' => 'Private description',
            'approved_at' => now(),
            'approved_by_user_id' => $actor->id,
        ]);
        Cache::put('translations.dashboard.stats', ['stale' => true], 60);

        $outdated = app(MarkBrandTranslationOutdatedAction::class)->handle($actor, $brand, $locale);

        $this->assertSame(TranslationStatus::Outdated, $outdated->status);
        $this->assertSame('Privater Markenname', $outdated->name);
        $this->assertSame('Private Tagline', $outdated->tagline);
        $this->assertSame('Private description', $outdated->description);
        $this->assertNull($outdated->approved_at);
        $this->assertNull($outdated->approved_by_user_id);
        $this->assertFalse(Cache::has('translations.dashboard.stats'));
        $this->assertSame(CentralBrandStatus::Active, $brand->fresh()->status);
        $this->assertSame(
            AuditAction::TranslationMarkedOutdated->value,
            $this->workflowAuditQuery($brand, AuditAction::TranslationMarkedOutdated)->sole()->action,
        );

        app(MarkBrandTranslationOutdatedAction::class)->handle($actor, $brand, $locale);
        $this->assertSame(1, $this->workflowAuditQuery($brand, AuditAction::TranslationMarkedOutdated)->count());
    }

    public function test_audit_failure_rolls_back_approval_and_mark_outdated(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $approvalLocale = Locale::factory()->create(['code' => 'de-DE']);
        $outdatedLocale = Locale::factory()->create(['code' => 'fr-FR']);
        $reviewed = $this->translation($brand, $approvalLocale, TranslationStatus::HumanReviewed);
        $approved = $this->translation($brand, $outdatedLocale, TranslationStatus::Approved, [
            'approved_at' => now(),
            'approved_by_user_id' => $actor->id,
        ]);
        $audit = $this->createMock(AuditRecorder::class);
        $audit->method('record')->willThrowException(new RuntimeException('audit unavailable'));
        $this->app->instance(AuditRecorder::class, $audit);

        foreach ([
            fn () => app(ApproveBrandTranslationAction::class)->handle($actor, $brand, $approvalLocale),
            fn () => app(MarkBrandTranslationOutdatedAction::class)->handle($actor, $brand, $outdatedLocale),
        ] as $mutation) {
            try {
                $mutation();
                $this->fail('Expected audit failure.');
            } catch (RuntimeException $exception) {
                $this->assertSame('audit unavailable', $exception->getMessage());
            }
        }

        $this->assertSame(TranslationStatus::HumanReviewed, $reviewed->fresh()->status);
        $this->assertSame(TranslationStatus::Approved, $approved->fresh()->status);
        $this->assertNotNull($approved->fresh()->approved_at);
        $this->assertSame(0, $this->workflowAuditQuery($brand)->count());
    }

    public function test_mark_outdated_keeps_dashboard_cache_when_the_transaction_rolls_back(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $translation = $this->translation($brand, $locale, TranslationStatus::Approved, [
            'approved_at' => now(),
            'approved_by_user_id' => $actor->id,
        ]);
        Cache::put('translations.dashboard.stats', ['stale' => true], 60);
        $audit = $this->createMock(AuditRecorder::class);
        $audit->method('record')->willThrowException(new RuntimeException('audit unavailable'));
        $this->app->instance(AuditRecorder::class, $audit);

        try {
            app(MarkBrandTranslationOutdatedAction::class)->handle($actor, $brand, $locale);
            $this->fail('Expected audit failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('audit unavailable', $exception->getMessage());
        }

        $this->assertTrue(Cache::has('translations.dashboard.stats'));
        $this->assertSame(TranslationStatus::Approved, $translation->fresh()->status);
    }

    public function test_http_workflow_requires_translation_authority_and_an_existing_active_locale_row(): void
    {
        $translator = User::factory()->create(['role' => UserRole::Translator]);
        $catalogManager = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $this->translation($brand, $locale, TranslationStatus::HumanReviewed);

        $this->actingAs($catalogManager)
            ->post(route('central.brands.translations.approve', [$brand, $locale->code]))
            ->assertForbidden();
        $this->actingAs($translator)
            ->post(route('central.brands.translations.approve', [$brand, $locale->code]))
            ->assertRedirect(route('central.brands.translations.edit', [$brand, $locale->code]));

        $missing = Locale::factory()->create(['code' => 'fr-FR']);
        $this->post(route('central.brands.translations.approve', [$brand, $missing->code]))->assertNotFound();
        $this->post(route('central.brands.translations.outdated', [$brand, $missing->code]))->assertNotFound();
    }

    /** @param array<string, mixed> $overrides */
    private function translation(CentralBrand $brand, Locale $locale, TranslationStatus $status, array $overrides = []): BrandTranslation
    {
        return BrandTranslation::factory()->create([
            'brand_id' => $brand->id,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
            'status' => $status,
            'source_hash' => app(TranslationSourceHashService::class)->forBrand($brand),
            ...$overrides,
        ]);
    }

    /** @return Builder<AuditLogEntry> */
    private function workflowAuditQuery(CentralBrand $brand, ?AuditAction $action = null): Builder
    {
        return AuditLogEntry::query()
            ->whereIn('action', [
                AuditAction::TranslationApproved->value,
                AuditAction::TranslationMarkedOutdated->value,
            ])
            ->where('subject_type', $brand->getMorphClass())
            ->where('subject_id', (string) $brand->getKey())
            ->when($action, static fn ($query) => $query->where('action', $action->value));
    }
}
