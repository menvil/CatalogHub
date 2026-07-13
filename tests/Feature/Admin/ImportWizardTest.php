<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Filament\Pages\ImportWizard;
use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use App\Models\Imports\RawProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_upload_starts_import_through_service_and_creates_batch(): void
    {
        Storage::fake('local');
        $admin = User::factory()->centralAdmin()->create();
        $source = ImportSource::factory()->create([
            'type' => ImportSource::TYPE_SERIALIZED_PHP,
        ]);
        $upload = UploadedFile::fake()->createWithContent(
            'products.phpdata',
            file_get_contents(base_path('tests/Fixtures/imports/serialized_products_sample.phpdata')),
        );

        Livewire::actingAs($admin)
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
}
