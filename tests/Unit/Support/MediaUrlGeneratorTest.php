<?php

namespace Tests\Unit\Support;

use App\Support\Media\MediaUrlGenerator;
use Tests\TestCase;

class MediaUrlGeneratorTest extends TestCase
{
    public function test_media_url_generator_builds_url_through_storage_disk(): void
    {
        config([
            'cataloghub_media.url_disk' => 'public_media',
            'filesystems.disks.public_media.url' => 'https://cdn.example.test/storage/media',
        ]);

        $url = app(MediaUrlGenerator::class)->url('products/example.txt');

        $this->assertSame('https://cdn.example.test/storage/media/products/example.txt', $url);
    }

    public function test_media_url_generator_service_resolves_from_container(): void
    {
        $this->assertInstanceOf(MediaUrlGenerator::class, app(MediaUrlGenerator::class));
    }
}
