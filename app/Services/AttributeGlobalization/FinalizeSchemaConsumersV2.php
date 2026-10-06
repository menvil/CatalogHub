<?php

namespace App\Services\AttributeGlobalization;

use App\Domains\Projections\SiteSyncService;
use App\Enums\Permission;
use App\Models\CentralCatalog\AttributeIdentityScope;
use App\Models\Site;
use App\Models\SiteCategoryProjection;
use App\Models\SiteProductProjection;
use App\Models\SiteSearchDocument;
use App\Models\User;
use App\Queries\Attributes\SchemaConsumerPreflightV2Query;
use App\Services\Categories\CategoryAccess;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Post-DDL runtime rebuild; never called from a historical migration. */
final class FinalizeSchemaConsumersV2
{
    /** @return array<string, mixed> */
    public function run(User $actor): array
    {
        app(CategoryAccess::class)->authorize(Permission::CatalogSchemaManage, $actor);
        $result = [];
        app(AttributeIdentityLock::class)->deployment(function () use (&$result): void {
            $result = DB::transaction(function (): array {
                app(AttributeIdentityLock::class)->acquire();
                $scope = AttributeIdentityScope::query()->findOrFail(1);
                if (! $scope->getAttribute('cutover_ready_for_finalization')) {
                    throw new RuntimeException('V2 contraction has not completed; restore/recover the migration before finalizing.');
                }
                if ((int) $scope->getAttribute('consumer_version') === 2) {
                    return ['consumer_version' => 2, 'already_finalized' => true];
                }
                if ((int) $scope->getAttribute('consumer_version') !== 0) {
                    throw new RuntimeException('Unexpected consumer version; no finalization applied.');
                }
                app(SchemaConsumerPreflightV2Query::class)->assertReady();
                $totals = ['sites' => 0, 'categories' => 0, 'products' => 0];
                foreach (Site::query()->orderBy('id')->cursor() as $site) {
                    $counts = app(SiteSyncService::class)->syncSite($site);
                    if ($counts['failures'] !== []) {
                        throw new RuntimeException('V2 rebuild failed; writers remain paused.');
                    }
                    $locales = $site->locales()->enabled()->ordered()->pluck('locale_code')->all();
                    if ($locales === []) {
                        $locales = [$site->default_locale];
                    }
                    foreach ([SiteProductProjection::class => $counts['products'], SiteCategoryProjection::class => $counts['categories']] as $model => $expected) {
                        $actual = $model::query()->where('site_id', $site->id)->whereIn('locale', $locales)
                            ->where('attribute_identity_version', 2)->whereNull('stale_at')->count();
                        if ($actual !== $expected) {
                            throw new RuntimeException('V2 projection verification failed; writers remain paused.');
                        }
                    }
                    $searchCount = SiteSearchDocument::query()->where('site_id', $site->id)->whereIn('locale', $locales)
                        ->whereIn('document_type', ['product', 'category'])->where('attribute_identity_version', 2)->whereNull('stale_at')->count();
                    if ($searchCount !== $counts['products'] + $counts['categories']) {
                        throw new RuntimeException('V2 search verification failed; writers remain paused.');
                    }
                    $totals['sites']++;
                    $totals['categories'] += $counts['categories'];
                    $totals['products'] += $counts['products'];
                }
                $scope->forceFill(['consumer_version' => 2])->saveOrFail();

                return ['consumer_version' => 2, 'already_finalized' => false, 'rebuilt' => $totals];
            });
        });

        return $result;
    }
}
