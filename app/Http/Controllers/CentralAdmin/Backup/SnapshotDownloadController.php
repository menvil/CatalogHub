<?php

namespace App\Http\Controllers\CentralAdmin\Backup;

use App\Http\Controllers\Controller;
use App\Models\CatalogSnapshot;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SnapshotDownloadController extends Controller
{
    public function __invoke(CatalogSnapshot $snapshot, string $fileKey): StreamedResponse
    {
        Gate::authorize('download', $snapshot);
        abort_unless($snapshot->isCompleted(), 404);

        $file = $snapshot->files_json[$fileKey] ?? null;
        abort_unless(is_array($file) && is_string($file['path'] ?? null), 404);

        $path = $file['path'];
        abort_unless($this->isSafeSnapshotPath($path), 404);

        $disk = is_string($file['disk'] ?? null) ? $file['disk'] : $snapshot->storage_disk;
        $filesystem = Storage::disk($disk);
        abort_unless($filesystem->exists($path), 404);

        return $filesystem->download($path, basename($path));
    }

    private function isSafeSnapshotPath(string $path): bool
    {
        return str_starts_with($path, 'snapshots/')
            && ! str_contains($path, '..')
            && ! str_contains($path, '\\')
            && ! str_contains($path, "\0")
            && ! str_starts_with($path, '/');
    }
}
