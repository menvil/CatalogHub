<?php

namespace Tests\Feature\Imports;

use App\Contracts\Imports\ProductImporterInterface;
use App\Models\Imports\ImportArtifact;
use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use App\Services\Imports\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportArtifactStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_original_upload_and_artifact_metadata_before_importing(): void
    {
        Storage::fake('imports');
        config()->set('imports.artifact_disk', 'imports');
        config()->set('imports.artifact_prefix', 'source-artifacts');

        $source = ImportSource::factory()->create();
        $upload = UploadedFile::fake()->createWithContent('products.phpdata', 'serialized-content');
        $importer = $this->createMock(ProductImporterInterface::class);
        $importer->method('supports')->willReturn(true);
        $importer->expects($this->once())
            ->method('import')
            ->with($this->isInstanceOf(ImportBatch::class), $upload, []);

        $batch = (new ImportService([$importer]))->startImport($source, $upload);
        $artifact = ImportArtifact::query()->sole();

        Storage::disk('imports')->assertExists($artifact->path);
        $this->assertSame($batch->id, $artifact->import_batch_id);
        $this->assertSame('original', $artifact->type);
        $this->assertSame('imports', $artifact->disk);
        $this->assertStringStartsWith('source-artifacts/', $artifact->path);
        $this->assertSame('products.phpdata', $artifact->original_filename);
        $this->assertSame(hash('sha256', 'serialized-content'), $artifact->checksum);
        $this->assertSame(strlen('serialized-content'), $artifact->file_size);
        $this->assertSame([], $artifact->metadata_json);
        $this->assertInstanceOf(ImportBatch::class, $artifact->batch()->getRelated());
    }
}
