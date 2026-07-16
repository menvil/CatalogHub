<?php

namespace Tests\Feature\Console;

use App\Models\CatalogSnapshot;
use App\Models\MediaManifest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerifyChecksumsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_detects_snapshot_checksum_mismatch(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('snapshots/test/products.jsonl', "{\"id\":1}\n");
        $snapshot = CatalogSnapshot::factory()->completed()->create([
            'storage_disk' => 'local',
            'files_json' => [
                'products' => [
                    'path' => 'snapshots/test/products.jsonl',
                    'checksum' => 'wrong-checksum',
                ],
            ],
        ]);

        $this->artisan('cataloghub:verify-checksums', ['--snapshot' => $snapshot->uuid])
            ->expectsOutputToContain('checksum mismatch')
            ->assertExitCode(1);

        $this->assertDatabaseHas('sync_logs', [
            'operation' => 'verify_snapshot_checksums',
            'status' => 'failed',
        ]);
    }

    public function test_command_passes_valid_snapshot_and_media_checksums(): void
    {
        Storage::fake('local');
        Storage::fake('media');
        $snapshotContent = "{\"id\":1}\n";
        Storage::disk('local')->put('snapshots/test/products.jsonl', $snapshotContent);
        $snapshot = CatalogSnapshot::factory()->completed()->create([
            'storage_disk' => 'local',
            'files_json' => [
                'products' => [
                    'path' => 'snapshots/test/products.jsonl',
                    'checksum' => hash('sha256', $snapshotContent),
                ],
            ],
        ]);
        $mediaContent = 'valid-media';
        Storage::disk('media')->put('originals/valid.jpg', $mediaContent);
        MediaManifest::factory()->create([
            'catalog_snapshot_id' => null,
            'original_path' => 'originals/valid.jpg',
            'checksum' => 'sha256:'.hash('sha256', $mediaContent),
            'metadata_json' => ['original_disk' => 'media'],
        ]);

        $this->artisan('cataloghub:verify-checksums', [
            '--snapshot' => $snapshot->uuid,
            '--media' => true,
        ])
            ->expectsOutputToContain('Verified 2 file checksum(s)')
            ->assertExitCode(0);
    }

    public function test_command_reports_missing_snapshot_file_separately(): void
    {
        Storage::fake('local');
        $snapshot = CatalogSnapshot::factory()->completed()->create([
            'storage_disk' => 'local',
            'files_json' => [
                'products' => [
                    'path' => 'snapshots/missing/products.jsonl',
                    'checksum' => hash('sha256', 'missing'),
                ],
            ],
        ]);

        $this->artisan('cataloghub:verify-checksums', ['--snapshot' => $snapshot->uuid])
            ->expectsOutputToContain('missing file')
            ->assertExitCode(1);
    }
}
