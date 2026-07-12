<?php

namespace Tests\Unit\Models;

use App\Models\Imports\ImportSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_config_and_exposes_source_state_helpers(): void
    {
        $source = ImportSource::factory()->create([
            'type' => ImportSource::TYPE_SERIALIZED_PHP,
            'status' => 'active',
            'config_json' => ['disk' => 'imports'],
        ])->fresh();

        $this->assertSame(['disk' => 'imports'], $source->config_json);
        $this->assertTrue($source->isActive());
        $this->assertTrue($source->isType(ImportSource::TYPE_SERIALIZED_PHP));
        $this->assertTrue($source->isSerializedPhp());
        $this->assertFalse($source->isType(ImportSource::TYPE_CSV));
    }

    public function test_has_many_import_batches(): void
    {
        $source = ImportSource::factory()->create();
        $batchId = DB::table('import_batches')->insertGetId([
            'import_source_id' => $source->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame($batchId, $source->batches()->sole()->id);
    }
}
