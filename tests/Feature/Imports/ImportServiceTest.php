<?php

namespace Tests\Feature\Imports;

use App\Contracts\Imports\ProductImporterInterface;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use App\Services\Imports\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_batch_and_delegates_import_to_matching_adapter(): void
    {
        Storage::fake('imports');
        $source = ImportSource::factory()->create();
        $upload = UploadedFile::fake()->createWithContent('products.data', 'products');
        $importer = $this->createMock(ProductImporterInterface::class);
        $importer->expects($this->once())
            ->method('supports')
            ->with($source)
            ->willReturn(true);
        $importer->expects($this->once())
            ->method('import')
            ->with(
                $this->isInstanceOf(ImportBatch::class),
                $upload,
                ['locale' => 'bg']
            );

        $batch = (new ImportService([$importer]))->startImport(
            $source,
            $upload,
            ['locale' => 'bg']
        );

        $this->assertSame('completed', $batch->fresh()->status);
        $this->assertSame('products.data', $batch->original_filename);
        $this->assertSame($source->id, $batch->import_source_id);
        $this->assertSame(0, CentralProduct::query()->count());
    }

    public function test_marks_batch_failed_when_adapter_throws(): void
    {
        Storage::fake('imports');
        $source = ImportSource::factory()->create();
        $upload = UploadedFile::fake()->createWithContent('broken.data', 'broken');
        $importer = $this->createMock(ProductImporterInterface::class);
        $importer->method('supports')->willReturn(true);
        $importer->method('import')->willThrowException(new RuntimeException('Broken import'));

        try {
            (new ImportService([$importer]))->startImport($source, $upload);
            $this->fail('The importer exception was not rethrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Broken import', $exception->getMessage());
        }

        $batch = ImportBatch::query()->sole();

        $this->assertSame('failed', $batch->status);
        $this->assertSame('Broken import', $batch->error_message);
        $this->assertNotNull($batch->finished_at);
    }
}
