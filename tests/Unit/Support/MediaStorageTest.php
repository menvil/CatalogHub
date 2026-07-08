<?php

namespace Tests\Unit\Support;

use App\Support\Media\MediaStorage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaStorageTest extends TestCase
{
    public function test_media_storage_stores_file_on_configured_disk(): void
    {
        Storage::fake('media');
        config([
            'cataloghub_media.disk' => 'media',
            'cataloghub_media.path_prefix' => 'media',
        ]);

        $path = app(MediaStorage::class)->put('products/example.txt', 'ok');

        $this->assertSame('media/products/example.txt', $path);
        Storage::disk('media')->assertExists($path);
        $this->assertSame('ok', Storage::disk('media')->get($path));
    }

    public function test_media_storage_service_resolves_from_container(): void
    {
        $this->assertInstanceOf(MediaStorage::class, app(MediaStorage::class));
    }
}
