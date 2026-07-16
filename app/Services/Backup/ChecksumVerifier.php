<?php

namespace App\Services\Backup;

use App\Models\CatalogSnapshot;
use App\Models\MediaManifest;
use Illuminate\Support\Facades\Storage;

final class ChecksumVerifier
{
    public function verify(?string $snapshotUuid = null, bool $includeMedia = false): ChecksumVerificationResult
    {
        $checkedCount = 0;
        $issues = [];
        $snapshots = CatalogSnapshot::query()
            ->where('status', 'completed')
            ->when($snapshotUuid !== null, fn ($query) => $query->where('uuid', $snapshotUuid))
            ->cursor();
        $snapshotFound = false;

        foreach ($snapshots as $snapshot) {
            $snapshotFound = true;

            foreach ($snapshot->files_json ?? [] as $fileKey => $file) {
                if (! is_array($file) || blank($file['checksum'] ?? null) || blank($file['path'] ?? null)) {
                    continue;
                }

                $checkedCount++;
                $disk = (string) ($file['disk'] ?? $snapshot->storage_disk);
                $path = (string) $file['path'];
                $this->verifyFile(
                    $disk,
                    $path,
                    (string) $file['checksum'],
                    "snapshot {$snapshot->uuid}/{$fileKey}",
                    $issues,
                );
            }
        }

        if ($snapshotUuid !== null && ! $snapshotFound) {
            $issues[] = "snapshot not found or not completed: {$snapshotUuid}";
        }

        if ($includeMedia) {
            $seenAssetIds = [];

            foreach (MediaManifest::query()->whereNotNull('checksum')->with('mediaAsset')->lazyById() as $manifest) {
                if ($manifest->media_asset_id !== null && in_array($manifest->media_asset_id, $seenAssetIds, true)) {
                    continue;
                }

                if ($manifest->media_asset_id !== null) {
                    $seenAssetIds[] = $manifest->media_asset_id;
                }

                if (blank($manifest->original_path)) {
                    continue;
                }

                $checkedCount++;
                $disk = (string) ($manifest->metadata_json['original_disk'] ?? $manifest->mediaAsset?->disk ?? 'media');
                $this->verifyFile(
                    $disk,
                    (string) $manifest->original_path,
                    (string) $manifest->checksum,
                    'media '.($manifest->asset_uuid ?? $manifest->getKey()),
                    $issues,
                );
            }
        }

        return new ChecksumVerificationResult($checkedCount, $issues);
    }

    /** @param list<string> $issues */
    private function verifyFile(
        string $disk,
        string $path,
        string $expectedChecksum,
        string $label,
        array &$issues,
    ): void {
        $filesystem = Storage::disk($disk);

        if (! $filesystem->exists($path)) {
            $issues[] = "missing file: {$label} [{$disk}:{$path}]";

            return;
        }

        $stream = $filesystem->readStream($path);

        if ($stream === false) {
            $issues[] = "unreadable file: {$label} [{$disk}:{$path}]";

            return;
        }

        try {
            $hash = hash_init('sha256');
            hash_update_stream($hash, $stream);
            $actualChecksum = hash_final($hash);
        } finally {
            fclose($stream);
        }

        $expectedChecksum = strtolower(preg_replace('/^sha256:/i', '', $expectedChecksum) ?? $expectedChecksum);

        if (! hash_equals($expectedChecksum, $actualChecksum)) {
            $issues[] = "checksum mismatch: {$label} [{$disk}:{$path}]";
        }
    }
}
