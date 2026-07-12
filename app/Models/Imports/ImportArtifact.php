<?php

namespace App\Models\Imports;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'import_batch_id',
    'type',
    'disk',
    'path',
    'original_filename',
    'mime_type',
    'file_size',
    'checksum',
    'metadata_json',
])]
final class ImportArtifact extends Model
{
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'metadata_json' => 'array',
        ];
    }

    /** @return BelongsTo<ImportBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }
}
