<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\CentralCatalog\ActivateCentralBrandAction;
use App\Actions\CentralCatalog\ArchiveCentralBrandAction;
use App\Actions\CentralCatalog\AssignCentralBrandOwnerAction;
use App\Actions\CentralCatalog\ClearCentralBrandOwnerAction;
use App\Actions\CentralCatalog\CreateOrganizationAndAssignCentralBrandOwnerAction;
use App\Actions\CentralCatalog\RestoreCentralBrandAction;
use App\Enums\AuditAction;
use App\Enums\CentralBrandStatus;
use App\Enums\TranslationStatus;
use App\Enums\UserRole;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralBrandOwnership;
use App\Models\Locale;
use App\Models\Organization;
use App\Models\Translations\BrandTranslation;
use App\Models\User;
use App\Queries\CentralCatalog\OrganizationSearchQuery;
use App\Services\Audit\AuditRecorder;
use App\Services\CentralCatalog\CentralBrandQualityEvaluator;
use App\Services\Translations\TranslationSourceHashService;
use App\Support\Normalization\OrganizationNameNormalizer;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

final class CentralBrandOwnershipActionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_assign_replace_clear_and_no_op_use_one_relation_and_minimized_audit(): void
    {
        CarbonImmutable::setTestNow('2026-08-31 10:00:00 UTC');
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->active()->create();
        $organizationA = Organization::factory()->create(['name' => 'Acme Holdings']);
        $organizationB = Organization::factory()->create(['name' => 'Beta Group']);
        $assign = app(AssignCentralBrandOwnerAction::class);

        $assigned = $assign->handle($actor, $brand, $organizationA);
        $ownership = $assigned->ownership;
        self::assertNotNull($ownership);
        self::assertSame($organizationA->id, $ownership->organization_id);
        $createdAt = $ownership->getRawOriginal('updated_at');

        CarbonImmutable::setTestNow('2026-08-31 10:00:02 UTC');
        $noOp = $assign->handle($actor, $brand, $organizationA);
        self::assertSame($createdAt, $noOp->ownership->getRawOriginal('updated_at'));
        self::assertSame(1, $this->auditQuery($brand)->count());

        $replaced = $assign->handle($actor, $brand, $organizationB);
        self::assertSame($ownership->id, $replaced->ownership->id);
        self::assertSame($organizationB->id, $replaced->ownership->organization_id);
        self::assertDatabaseHas('organizations', ['id' => $organizationA->id]);

        $replaceAudit = $this->auditQuery($brand, AuditAction::CatalogBrandOwnerAssigned)->latest('id')->firstOrFail();
        self::assertSame([
            'organization_id' => $organizationA->id,
            'organization_name' => 'Acme Holdings',
        ], $replaceAudit->before_json);
        self::assertSame([
            'organization_id' => $organizationB->id,
            'organization_name' => 'Beta Group',
        ], $replaceAudit->after_json);

        $cleared = app(ClearCentralBrandOwnerAction::class)->handle($actor, $brand);
        self::assertNull($cleared->ownership);
        self::assertDatabaseCount('central_brand_ownerships', 0);
        self::assertDatabaseHas('organizations', ['id' => $organizationB->id]);
        self::assertSame(CentralBrandStatus::Active, $brand->fresh()->status);
        self::assertSame(3, $this->auditQuery($brand)->count());

        app(ClearCentralBrandOwnerAction::class)->handle($actor, $brand);
        self::assertSame(3, $this->auditQuery($brand)->count());
    }

    public function test_create_and_assign_normalizes_unicode_without_global_name_uniqueness(): void
    {
        $actor = User::factory()->create();
        $brandA = CentralBrand::factory()->create();
        $brandB = CentralBrand::factory()->create();
        $action = app(CreateOrganizationAndAssignCentralBrandOwnerAction::class);

        $action->handle($actor, $brandA, "  Société\u{00A0}Générale  ");
        $action->handle($actor, $brandB, 'société générale');

        self::assertDatabaseCount('organizations', 2);
        $organizations = Organization::query()->orderBy('id')->get();
        self::assertSame('Société Générale', $organizations[0]->name);
        self::assertSame(
            OrganizationNameNormalizer::search('Société Générale'),
            $organizations[0]->normalized_name,
        );
        self::assertSame($organizations[0]->normalized_name, $organizations[1]->normalized_name);
        self::assertSame($organizations[0]->id, $brandA->fresh()->ownership?->organization_id);
        self::assertSame($organizations[1]->id, $brandB->fresh()->ownership?->organization_id);
    }

    public function test_create_and_assign_persists_full_expanding_unicode_normalization_with_bounded_search_prefix(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $name = str_repeat('ﬃ', 200);

        app(CreateOrganizationAndAssignCentralBrandOwnerAction::class)->handle($actor, $brand, $name);

        $organization = Organization::query()->sole();
        self::assertSame(600, mb_strlen($organization->normalized_name));
        self::assertSame(
            OrganizationNameNormalizer::prefixForNormalizedName($organization->normalized_name),
            $organization->normalized_name_prefix,
        );
        self::assertSame(OrganizationNameNormalizer::SEARCH_PREFIX_LENGTH, mb_strlen($organization->normalized_name_prefix));
        self::assertSame($organization->id, $brand->fresh()->ownership?->organization_id);
        self::assertSame(
            [(string) $organization->id],
            collect(app(OrganizationSearchQuery::class)->search($name))->pluck('value')->all(),
        );
    }

    public function test_create_rejects_blank_control_and_overlong_names_without_changing_ownership(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $existing = Organization::factory()->create();
        app(AssignCentralBrandOwnerAction::class)->handle($actor, $brand, $existing);
        $action = app(CreateOrganizationAndAssignCentralBrandOwnerAction::class);

        foreach (['   ', "\u{00A0}", "Invalid\nName", "Invalid\xC3\x28", str_repeat('A', 256)] as $invalidName) {
            try {
                $action->handle($actor, $brand, $invalidName);
                self::fail('Expected invalid Organization name.');
            } catch (ValidationException) {
                self::assertSame($existing->id, $brand->fresh()->ownership?->organization_id);
            }
        }

        self::assertDatabaseCount('organizations', 1);
        self::assertSame(1, $this->auditQuery($brand)->count());
    }

    public function test_audit_failure_rolls_back_assignment_clear_and_create_without_orphans(): void
    {
        $actor = User::factory()->create();
        $newBrand = CentralBrand::factory()->create();
        $ownedBrand = CentralBrand::factory()->create();
        $organization = Organization::factory()->create();
        CentralBrandOwnership::factory()->create([
            'central_brand_id' => $ownedBrand->id,
            'organization_id' => $organization->id,
        ]);
        $audit = $this->createMock(AuditRecorder::class);
        $audit->method('record')->willThrowException(new RuntimeException('audit unavailable'));
        $this->app->instance(AuditRecorder::class, $audit);

        foreach ([
            fn () => app(AssignCentralBrandOwnerAction::class)->handle($actor, $newBrand, $organization),
            fn () => app(ClearCentralBrandOwnerAction::class)->handle($actor, $ownedBrand),
            fn () => app(CreateOrganizationAndAssignCentralBrandOwnerAction::class)->handle($actor, $newBrand, 'Transient Holdings'),
        ] as $mutation) {
            try {
                $mutation();
                self::fail('Expected audit failure.');
            } catch (RuntimeException $exception) {
                self::assertSame('audit unavailable', $exception->getMessage());
            }
        }

        self::assertNull($newBrand->fresh()->ownership);
        self::assertSame($organization->id, $ownedBrand->fresh()->ownership?->organization_id);
        self::assertDatabaseCount('organizations', 1);
    }

    public function test_all_ownership_actions_authorize_before_mutating_state(): void
    {
        $actor = User::factory()->create(['role' => UserRole::Translator]);
        $brand = CentralBrand::factory()->create();
        $existing = Organization::factory()->create(['name' => 'Existing Owner']);
        $target = Organization::factory()->create(['name' => 'Target Owner']);
        CentralBrandOwnership::factory()->create([
            'central_brand_id' => $brand->id,
            'organization_id' => $existing->id,
        ]);

        $mutations = [
            fn () => app(AssignCentralBrandOwnerAction::class)->handle($actor, $brand, $target),
            fn () => app(ClearCentralBrandOwnerAction::class)->handle($actor, $brand),
            fn () => app(CreateOrganizationAndAssignCentralBrandOwnerAction::class)
                ->handle($actor, $brand, 'Forbidden Organization'),
        ];

        foreach ($mutations as $mutation) {
            try {
                $mutation();
                self::fail('Expected ownership Action authorization to fail.');
            } catch (AuthorizationException) {
                self::assertSame($existing->id, $brand->fresh()->ownership?->organization_id);
                self::assertDatabaseCount('organizations', 2);
            }
        }

        self::assertSame(0, $this->auditQuery($brand)->count());
    }

    public function test_ownership_is_independent_from_lifecycle_quality_and_translation_source(): void
    {
        $actor = User::factory()->create();
        $organization = Organization::factory()->create();

        foreach (CentralBrandStatus::cases() as $status) {
            $brand = CentralBrand::factory()->create(['status' => $status]);
            app(AssignCentralBrandOwnerAction::class)->handle($actor, $brand, $organization);
            self::assertSame($status, $brand->fresh()->status);
        }

        $lifecycleBrand = CentralBrand::factory()->create();
        app(AssignCentralBrandOwnerAction::class)->handle($actor, $lifecycleBrand, $organization);
        app(ActivateCentralBrandAction::class)->handle($actor, $lifecycleBrand);
        self::assertSame($organization->id, $lifecycleBrand->fresh()->ownership?->organization_id);
        app(ArchiveCentralBrandAction::class)->handle($actor, $lifecycleBrand);
        self::assertSame($organization->id, $lifecycleBrand->fresh()->ownership->organization_id);
        app(RestoreCentralBrandAction::class)->handle($actor, $lifecycleBrand);
        self::assertSame(CentralBrandStatus::Draft, $lifecycleBrand->fresh()->status);
        self::assertSame($organization->id, $lifecycleBrand->fresh()->ownership->organization_id);

        $brand = CentralBrand::factory()->active()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $hash = app(TranslationSourceHashService::class)->forBrand($brand);
        $translation = BrandTranslation::factory()->create([
            'brand_id' => $brand->id,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
            'status' => TranslationStatus::Approved,
            'source_hash' => $hash,
            'approved_at' => now(),
            'approved_by_user_id' => $actor->id,
        ]);
        $evaluator = app(CentralBrandQualityEvaluator::class);
        $locales = new Collection([$locale]);
        $translations = new Collection([$locale->id => $translation]);
        $qualityBefore = $evaluator->evaluate($brand, $locales, $translations, false, false);

        app(AssignCentralBrandOwnerAction::class)->handle($actor, $brand, $organization);

        self::assertSame($hash, app(TranslationSourceHashService::class)->forBrand($brand->fresh()));
        self::assertSame(TranslationStatus::Approved, $translation->fresh()->status);
        self::assertNotNull($translation->approved_at);
        $qualityAfter = $evaluator->evaluate($brand->fresh(), $locales, $translations, false, false);
        self::assertSame($qualityBefore->state, $qualityAfter->state);
        self::assertSame($qualityBefore->score, $qualityAfter->score);
        self::assertSame($qualityBefore->completedChecks, $qualityAfter->completedChecks);
        self::assertSame($qualityBefore->totalChecks, $qualityAfter->totalChecks);
        self::assertSame($qualityBefore->issueCodes(), $qualityAfter->issueCodes());
    }

    /** @return Builder<AuditLogEntry> */
    private function auditQuery(CentralBrand $brand, ?AuditAction $action = null)
    {
        return AuditLogEntry::query()
            ->whereIn('action', [
                AuditAction::CatalogBrandOwnerAssigned->value,
                AuditAction::CatalogBrandOwnerCleared->value,
            ])
            ->where('subject_type', $brand->getMorphClass())
            ->where('subject_id', (string) $brand->getKey())
            ->when($action, static fn ($query) => $query->where('action', $action->value));
    }
}
