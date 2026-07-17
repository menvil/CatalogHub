<?php

namespace App\Services\Health;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Str;
use Throwable;

final class StorageHealthCheck
{
    public function __construct(private readonly FilesystemManager $filesystems) {}

    public function run(?string $diskName = null): HealthCheckResult
    {
        $diskName ??= config('cataloghub_media.disk');

        if (! is_string($diskName) || $diskName === '') {
            return new HealthCheckResult('error', 'No media storage disk is configured.');
        }

        $path = 'healthchecks/test-'.Str::uuid().'.tmp';
        $contents = Str::random(32);

        try {
            $disk = $this->filesystems->disk($diskName);
            $written = $disk->put($path, $contents);

            if (! $written || ! $disk->exists($path) || $disk->get($path) !== $contents) {
                return new HealthCheckResult('error', 'Storage write/read verification failed.', [
                    'disk' => $diskName,
                ]);
            }

            if (! $disk->delete($path) || $this->pathExists($disk, $path)) {
                return new HealthCheckResult('error', 'Storage cleanup verification failed.', [
                    'disk' => $diskName,
                ]);
            }

            return new HealthCheckResult('ok', 'Storage write, read, and delete checks passed.', [
                'disk' => $diskName,
            ]);
        } catch (Throwable $exception) {
            return new HealthCheckResult('error', 'Storage diagnostics are unavailable.', [
                'disk' => $diskName,
                'error_class' => $exception::class,
            ]);
        } finally {
            try {
                $this->filesystems->disk($diskName)->delete($path);
            } catch (Throwable) {
                // The result already reports an unavailable or unclean storage disk.
            }
        }
    }

    private function pathExists(Filesystem $disk, string $path): bool
    {
        return $disk->exists($path);
    }
}
