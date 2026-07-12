<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportArtifactsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_import_artifacts_table_with_storage_metadata(): void
    {
        $this->assertTrue(Schema::hasTable('import_artifacts'));
        $this->assertTrue(Schema::hasColumns('import_artifacts', [
            'id',
            'import_batch_id',
            'type',
            'disk',
            'path',
            'original_filename',
            'mime_type',
            'file_size',
            'checksum',
            'metadata_json',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_import_artifact_references_batch_and_has_storage_indexes(): void
    {
        $foreignKeys = collect(Schema::getForeignKeys('import_artifacts'));
        $indexes = collect(Schema::getIndexes('import_artifacts'));

        $this->assertTrue($foreignKeys->contains(
            fn (array $foreignKey): bool => $foreignKey['columns'] === ['import_batch_id']
                && $foreignKey['foreign_table'] === 'import_batches'
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['import_batch_id', 'type']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['disk', 'path']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['checksum']
        ));
    }
}
