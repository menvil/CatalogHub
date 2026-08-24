<?php

namespace App\Services\Media;

interface MediaStorage
{
    public function storeNormalized(string $disk, string $path, NormalizedImage $image): void;

    public function putContents(string $disk, string $path, string $contents): void;

    public function exists(string $disk, string $path): bool;

    public function read(string $disk, string $path): string;

    public function size(string $disk, string $path): int;

    public function delete(string $disk, string $path): void;
}
