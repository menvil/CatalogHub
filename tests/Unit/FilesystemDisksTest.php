<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FilesystemDisksTest extends TestCase
{
    public function test_cataloghub_filesystem_disks_are_configured(): void
    {
        foreach (['media', 'public_media', 'imports', 'exports', 'backups'] as $disk) {
            $this->assertIsArray(config("filesystems.disks.{$disk}"));
        }
    }

    public function test_storage_disks_can_write_and_read_files(): void
    {
        foreach (['media', 'imports', 'exports', 'backups'] as $disk) {
            Storage::fake($disk);

            Storage::disk($disk)->put('smoke.txt', 'ok');

            $this->assertSame('ok', Storage::disk($disk)->get('smoke.txt'));
        }
    }
}
