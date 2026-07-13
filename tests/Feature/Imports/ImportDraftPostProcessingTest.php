<?php

namespace Tests\Feature\Imports;

use App\Contracts\Imports\ProductImporterInterface;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Imports\DuplicateCandidate;
use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use App\Models\Imports\NormalizedProductDraft;
use App\Services\Imports\DuplicateDetector;
use App\Services\Imports\ImportMediaDownloader;
use App\Services\Imports\ImportService;
use App\Services\Imports\RawProductWriter;
use App\Services\Media\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportDraftPostProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_service_downloads_media_and_detects_duplicates_for_created_drafts(): void
    {
        Storage::fake('imports');
        Storage::fake('public');
        $image = UploadedFile::fake()->image('product.jpg', 100, 100);
        Http::fake([
            'https://cdn.example.test/product.jpg' => Http::response(
                file_get_contents($image->getRealPath()),
                200,
                ['Content-Type' => 'image/jpeg'],
            ),
        ]);
        CentralProduct::factory()->create(['name' => 'Imported Product']);
        $source = ImportSource::factory()->create();
        $artifact = UploadedFile::fake()->createWithContent('products.data', 'products');
        $importer = $this->createMock(ProductImporterInterface::class);
        $importer->method('supports')->willReturn(true);
        $importer->method('import')->willReturnCallback(function (ImportBatch $batch): void {
            $rawProduct = (new RawProductWriter)->write($batch, ['title' => 'Imported Product'], 1);

            $batch->drafts()->create([
                'raw_product_id' => $rawProduct->id,
                'title' => 'Imported Product',
                'normalized_payload_json' => ['title' => 'Imported Product'],
                'attributes_json' => [],
                'media_json' => [['source_url' => 'https://cdn.example.test/product.jpg']],
                'confidence' => '1.0000',
                'status' => 'pending_review',
            ]);
        });
        $mediaDownloader = new ImportMediaDownloader(
            app(MediaService::class),
            static fn (string $host): array => ['93.184.216.34'],
        );

        $importService = new ImportService([$importer], $mediaDownloader, new DuplicateDetector);

        $importService->startImport($source, $artifact);

        $draft = NormalizedProductDraft::query()->sole();
        $this->assertSame('downloaded', $draft->media_json[0]['status']);
        $this->assertIsInt($draft->media_json[0]['media_asset_id']);
        $this->assertSame($draft->id, DuplicateCandidate::query()->sole()->normalized_product_draft_id);
    }
}
