<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Filament\Pages\ImportWizard;
use App\Jobs\Imports\ProcessImportBatchJob;
use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use App\Models\Imports\RawProduct;
use App\Models\User;
use App\Services\Imports\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ImportWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_opens_and_shows_active_sources(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $source = ImportSource::factory()->create(['name' => 'Legacy serialized catalog']);

        $this->actingAs($admin)
            ->get(ImportWizard::getUrl())
            ->assertOk()
            ->assertSee('Import Wizard')
            ->assertSee($source->name)
            ->assertSee('Start import');
    }

    public function test_hides_active_sources_without_a_registered_importer(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $supported = ImportSource::factory()->create([
            'name' => 'Serialized products',
            'type' => ImportSource::TYPE_SERIALIZED_PHP,
        ]);
        $unsupported = ImportSource::factory()->create([
            'name' => 'Future JSON feed',
            'type' => ImportSource::TYPE_JSON,
        ]);

        $this->actingAs($admin)
            ->get(ImportWizard::getUrl())
            ->assertOk()
            ->assertSee($supported->name)
            ->assertDontSee($unsupported->name);
    }

    public function test_upload_starts_import_through_service_and_creates_batch(): void
    {
        Storage::fake('imports');
        $admin = User::factory()->centralAdmin()->create();
        $source = ImportSource::factory()->create([
            'type' => ImportSource::TYPE_SERIALIZED_PHP,
        ]);
        $upload = UploadedFile::fake()->createWithContent(
            'products.phpdata',
            file_get_contents(base_path('tests/Fixtures/imports/serialized_products_sample.phpdata')),
        );

        $component = Livewire::actingAs($admin)
            ->test(ImportWizard::class)
            ->set('sourceId', $source->id)
            ->set('artifact', $upload)
            ->set('locale', 'bg-BG')
            ->call('startImport')
            ->assertHasNoErrors()
            ->assertSet('createdBatchId', fn (?int $id): bool => $id !== null);

        $batch = ImportBatch::query()->sole();
        $this->assertSame('completed', $batch->status);
        $this->assertSame('bg-BG', $batch->metadata_json['locale']);
        $this->assertSame(2, RawProduct::query()->count());

        $component->call('startImport');

        $this->assertSame(1, ImportBatch::query()->count());
        $this->assertSame(2, RawProduct::query()->count());
    }

    public function test_page_access_is_limited_to_catalog_management_roles(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $editor = User::factory()->create(['role' => UserRole::CatalogEditor]);

        $this->actingAs($moderator);
        $this->assertFalse(ImportWizard::canAccess());

        $this->actingAs($editor);
        $this->assertTrue(ImportWizard::canAccess());
    }

    public function test_rejects_artifacts_with_an_unsupported_mime_type(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $source = ImportSource::factory()->create([
            'type' => ImportSource::TYPE_SERIALIZED_PHP,
        ]);

        Livewire::actingAs($admin)
            ->test(ImportWizard::class)
            ->set('sourceId', $source->id)
            ->set('artifact', UploadedFile::fake()->image('products.png'))
            ->call('startImport')
            ->assertHasErrors(['artifact']);

        $this->assertSame(0, ImportBatch::query()->count());
    }

    public function test_large_artifact_is_queued_and_exposes_batch_status_for_polling(): void
    {
        Storage::fake('imports');
        Queue::fake();
        config()->set('imports.queued_artifact_threshold_bytes', 1);
        $admin = User::factory()->centralAdmin()->create();
        $source = ImportSource::factory()->create([
            'type' => ImportSource::TYPE_SERIALIZED_PHP,
        ]);
        $upload = UploadedFile::fake()->createWithContent(
            'products.phpdata',
            file_get_contents(base_path('tests/Fixtures/imports/serialized_products_sample.phpdata')),
        );

        $component = Livewire::actingAs($admin)
            ->test(ImportWizard::class)
            ->set('sourceId', $source->id)
            ->set('artifact', $upload)
            ->call('startImport')
            ->assertHasNoErrors()
            ->assertSet('batchStatus', 'pending');

        $batch = ImportBatch::query()->sole();
        Queue::assertPushed(
            ProcessImportBatchJob::class,
            fn (ProcessImportBatchJob $job): bool => $job->importBatchId === $batch->id,
        );
        $this->assertSame(0, RawProduct::query()->count());

        (new ProcessImportBatchJob($batch->id))->handle(app(ImportService::class));

        $component
            ->call('refreshBatchStatus')
            ->assertSet('batchStatus', 'completed');
        $this->assertSame(2, RawProduct::query()->count());
    }
}
