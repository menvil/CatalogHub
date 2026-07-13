<?php

namespace Database\Factories;

use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportBatch>
 */
class ImportBatchFactory extends Factory
{
    protected $model = ImportBatch::class;

    public function definition(): array
    {
        return [
            'import_source_id' => ImportSource::factory(),
            'status' => 'pending',
            'original_filename' => fake()->optional()->passthrough(fake()->word().'.data'),
            'total_items' => 0,
            'raw_items_count' => 0,
            'drafts_count' => 0,
            'approved_count' => 0,
            'rejected_count' => 0,
            'failed_count' => 0,
            'started_at' => null,
            'finished_at' => null,
            'error_message' => null,
            'metadata_json' => [],
        ];
    }
}
