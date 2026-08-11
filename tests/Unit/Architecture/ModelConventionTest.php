<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Enums\SiteDomainType;
use App\Enums\SiteMembershipRole;
use App\Enums\SiteStatus;
use App\Models\AuditLogEntry;
use App\Models\Site;
use App\Models\SiteDomain;
use App\Models\SiteLocale;
use App\Models\SiteMembership;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

final class ModelConventionTest extends TestCase
{
    public function test_foundation_models_keep_database_integer_keys_and_typed_lifecycle_contracts(): void
    {
        foreach ([Site::class, SiteDomain::class, SiteLocale::class, SiteMembership::class, AuditLogEntry::class] as $modelClass) {
            $model = new $modelClass;

            $this->assertSame('int', $model->getKeyType());
            $this->assertTrue($model->getIncrementing());
        }

        $this->assertSame(SiteStatus::class, (new Site)->getCasts()['status']);
        $this->assertSame(SiteDomainType::class, (new SiteDomain)->getCasts()['type']);
        $this->assertSame(SiteMembershipRole::class, (new SiteMembership)->getCasts()['role']);
    }

    public function test_site_is_the_only_soft_deleted_foundation_lifecycle_aggregate_and_audit_rows_are_append_only(): void
    {
        $this->assertContains(SoftDeletes::class, class_uses_recursive(Site::class));

        foreach ([SiteDomain::class, SiteLocale::class, SiteMembership::class, AuditLogEntry::class] as $modelClass) {
            $this->assertNotContains(SoftDeletes::class, class_uses_recursive($modelClass));
        }

        $this->assertNull((new AuditLogEntry)->getUpdatedAtColumn());
    }
}
