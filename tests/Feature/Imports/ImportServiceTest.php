<?php

namespace Tests\Feature\Imports;

use App\Contracts\Imports\ProductImporterInterface;
use App\Jobs\Imports\ProcessImportDraftsJob;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Imports\DuplicateCandidate;
use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use App\Services\Imports\DuplicateDetector;
use App\Services\Imports\ImportService;
use App\Services\Imports\RawProductWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
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

    public function test_unreadable_stored_artifact_does_not_leave_a_temporary_file(): void
    {
        Storage::fake('imports');
        Queue::fake();
        $source = ImportSource::factory()->create();
        $upload = UploadedFile::fake()->createWithContent('products.data', 'products');
        $importer = $this->createMock(ProductImporterInterface::class);
        $importer->method('supports')->willReturn(true);
        $service = new ImportService([$importer]);
        $batch = $service->queueImport($source, $upload);
        $artifact = $batch->artifacts()->sole();
        Storage::disk($artifact->disk)->delete($artifact->path);
        $temporaryPattern = sys_get_temp_dir().'/cataloghub-import-artifact-*';
        $filesBefore = glob($temporaryPattern) ?: [];

        try {
            $service->processQueuedImport($batch);
            $this->fail('An unreadable stored artifact should fail the import.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The stored import artifact could not be read.', $exception->getMessage());
        }

        $this->assertSame($filesBefore, glob($temporaryPattern) ?: []);
        $this->assertSame('failed', $batch->fresh()->status);
    }

    public function test_a_one_shot_importer_iterable_can_be_reused_for_support_and_resolution(): void
    {
        Storage::fake('imports');
        $source = ImportSource::factory()->create();
        $upload = UploadedFile::fake()->createWithContent('products.data', 'products');
        $importer = $this->createMock(ProductImporterInterface::class);
        $importer->expects($this->exactly(2))->method('supports')->willReturn(true);
        $importer->expects($this->once())->method('import');
        $importers = (static function () use ($importer): \Generator {
            yield $importer;
        })();
        $service = new ImportService($importers);

        $this->assertTrue($service->supports($source));
        $batch = $service->startImport($source, $upload);

        $this->assertSame('completed', $batch->status);
    }

    public function test_draft_post_processing_is_queued_instead_of_running_inline(): void
    {
        Storage::fake('imports');
        Queue::fake();
        config()->set('queue.default', 'database');
        $source = ImportSource::factory()->create();
        $upload = UploadedFile::fake()->createWithContent('products.data', 'products');
        $importer = $this->createMock(ProductImporterInterface::class);
        $importer->method('supports')->willReturn(true);
        $importer->method('import')->willReturnCallback(function (ImportBatch $batch): void {
            $rawProduct = (new RawProductWriter)->write($batch, ['title' => 'Queued draft'], 1);

            $batch->drafts()->create([
                'raw_product_id' => $rawProduct->id,
                'title' => 'Queued draft',
                'normalized_payload_json' => ['title' => 'Queued draft'],
                'attributes_json' => [],
                'media_json' => [],
                'confidence' => '1.0000',
                'status' => 'pending_review',
            ]);
        });

        $batch = (new ImportService([$importer], duplicateDetector: new DuplicateDetector))
            ->startImport($source, $upload);

        $this->assertSame('processing', $batch->status);
        Queue::assertPushed(
            ProcessImportDraftsJob::class,
            fn (ProcessImportDraftsJob $job): bool => $job->importBatchId === $batch->id,
        );
        $this->assertSame(0, DuplicateCandidate::query()->count());
    }
}
