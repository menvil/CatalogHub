<?php

namespace Tests\Feature\Health;

use App\Services\Health\StorageHealthCheck;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageHealthCheckTest extends TestCase
{
    public function test_it_verifies_storage_write_read_delete_and_cleans_up(): void
    {
        Storage::fake('media-health');

        $result = app(StorageHealthCheck::class)->run('media-health');

        $this->assertSame('ok', $result->status);
        $this->assertSame('media-health', $result->details['disk']);
        $this->assertSame([], Storage::disk('media-health')->allFiles('healthchecks'));
    }

    public function test_it_reports_an_invalid_storage_disk_without_exposing_credentials(): void
    {
        config()->set('filesystems.disks.broken-health', [
            'driver' => 'unsupported',
            'secret' => 'must-not-appear',
        ]);

        $result = app(StorageHealthCheck::class)->run('broken-health');

        $this->assertSame('error', $result->status);
        $this->assertSame('broken-health', $result->details['disk']);
        $this->assertStringNotContainsString('must-not-appear', $result->summary);
    }
}
