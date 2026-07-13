<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportBatchesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_import_batches_table_with_lifecycle_columns(): void
    {
        $this->assertTrue(Schema::hasTable('import_batches'));
        $this->assertTrue(Schema::hasColumns('import_batches', [
            'id',
            'import_source_id',
            'status',
            'original_filename',
            'total_items',
            'raw_items_count',
            'drafts_count',
            'approved_count',
            'rejected_count',
            'failed_count',
            'started_at',
            'finished_at',
            'error_message',
            'metadata_json',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_import_batch_references_its_source_and_defaults_counters_to_zero(): void
    {
        $foreignKeys = collect(Schema::getForeignKeys('import_batches'));

        $this->assertTrue($foreignKeys->contains(
            fn (array $foreignKey): bool => $foreignKey['columns'] === ['import_source_id']
                && $foreignKey['foreign_table'] === 'import_sources'
        ));

        $sourceId = DB::table('import_sources')->insertGetId([
            'code' => 'serialized-php',
            'name' => 'Serialized PHP',
            'type' => 'serialized_php',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $batchId = DB::table('import_batches')->insertGetId([
            'import_source_id' => $sourceId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $batch = (array) DB::table('import_batches')->find($batchId);

        $this->assertSame('pending', $batch['status']);
        $this->assertSame(0, $batch['total_items']);
        $this->assertSame(0, $batch['raw_items_count']);
        $this->assertSame(0, $batch['drafts_count']);
        $this->assertSame(0, $batch['approved_count']);
        $this->assertSame(0, $batch['rejected_count']);
        $this->assertSame(0, $batch['failed_count']);
    }
}
