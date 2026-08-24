<?php

namespace App\Services\Media;

use Illuminate\Filesystem\FilesystemManager;
use RuntimeException;

final readonly class FilesystemMediaStorage implements MediaStorage
{
    public function __construct(private FilesystemManager $filesystems) {}

    public function storeNormalized(string $disk, string $path, NormalizedImage $image): void
    {
        $this->putContents($disk, $path, $image->bytes);
    }

    public function putContents(string $disk, string $path, string $contents): void
    {
        if (! $this->filesystems->disk($disk)->put($path, $contents)) {
            throw new RuntimeException('Unable to store media.');
        }
    }

    public function exists(string $disk, string $path): bool
    {
        return $this->filesystems->disk($disk)->exists($path);
    }

    public function read(string $disk, string $path): string
    {
        $contents = $this->filesystems->disk($disk)->get($path);

        if (! is_string($contents)) {
            throw new RuntimeException('Unable to read media.');
        }

        return $contents;
    }

    public function size(string $disk, string $path): int
    {
        return (int) $this->filesystems->disk($disk)->size($path);
    }

    public function delete(string $disk, string $path): void
    {
        $this->filesystems->disk($disk)->delete($path);
    }
}
