<?php

namespace Tests\Feature\Audit;

use App\Models\AuditLogEntry;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class AuditLogSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_schema_contains_queryable_security_fields_and_indexes(): void
    {
        $this->assertTrue(Schema::hasColumns('audit_log_entries', [
            'id',
            'actor_id',
            'context',
            'site_id',
            'action',
            'subject_type',
            'subject_id',
            'before_json',
            'after_json',
            'request_id',
            'created_at',
        ]));

        $indexes = collect(Schema::getIndexes('audit_log_entries'));

        foreach ([
            ['actor_id', 'created_at'],
            ['site_id', 'created_at'],
            ['action', 'created_at'],
            ['request_id'],
        ] as $columns) {
            $this->assertTrue($indexes->contains(
                fn (array $index): bool => $index['unique'] === false && $index['columns'] === $columns,
            ));
        }
    }

    public function test_audit_entry_casts_whitelisted_snapshots_and_relations(): void
    {
        $actor = User::factory()->create();
        $site = Site::factory()->create();
        $entry = AuditLogEntry::factory()->for($actor, 'actor')->for($site)->create([
            'before_json' => ['role' => 'catalog_editor'],
            'after_json' => ['role' => 'central_admin'],
        ]);

        $entry->refresh();

        $this->assertSame(['role' => 'catalog_editor'], $entry->before_json);
        $this->assertSame(['role' => 'central_admin'], $entry->after_json);
        $this->assertTrue($entry->actor->is($actor));
        $this->assertTrue($entry->site->is($site));
    }

    public function test_audit_entries_cannot_be_updated_through_the_model(): void
    {
        $entry = AuditLogEntry::factory()->create();

        $this->expectException(LogicException::class);
        $entry->update(['action' => 'security.audit.rewritten']);
    }

    public function test_audit_entries_cannot_be_deleted_through_the_model(): void
    {
        $entry = AuditLogEntry::factory()->create();

        $this->expectException(LogicException::class);
        $entry->delete();
    }

    public function test_audit_entries_cannot_be_bulk_updated(): void
    {
        $entry = AuditLogEntry::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('audit_log_entries')
            ->where('id', $entry->getKey())
            ->update(['action' => 'security.audit.rewritten']);
    }

    public function test_audit_entries_cannot_be_deleted_with_direct_sql(): void
    {
        $entry = AuditLogEntry::factory()->create();

        $this->expectException(QueryException::class);

        DB::delete('DELETE FROM audit_log_entries WHERE id = ?', [$entry->getKey()]);
    }

    public function test_actor_attribution_prevents_user_deletion(): void
    {
        $entry = AuditLogEntry::factory()->for(Site::factory())->create();

        $this->expectException(QueryException::class);

        $entry->actor->delete();
    }

    public function test_site_attribution_prevents_site_deletion(): void
    {
        $entry = AuditLogEntry::factory()->for(Site::factory())->create();

        $this->expectException(QueryException::class);

        $entry->site->forceDelete();
    }
}
