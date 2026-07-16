<?php

namespace App\Services\Export;

use App\Models\CatalogSnapshot;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

final class JsonlStreamWriter
{
    /**
     * @param  iterable<array<string, mixed>>  $rows
     *
     * @throws JsonException
     */
    public function write(
        CatalogSnapshot $snapshot,
        string $fileKey,
        iterable $rows,
    ): JsonlExportResult {
        $disk = $snapshot->storage_disk;
        $basePath = $snapshot->storage_path ?: "snapshots/{$snapshot->uuid}";
        $path = "{$basePath}/{$fileKey}.jsonl";
        $temporaryPath = "{$path}.tmp-".Str::uuid();
        $stream = fopen('php://temp/maxmemory:1048576', 'w+b');

        if ($stream === false) {
            throw new RuntimeException('Unable to open the JSONL export stream.');
        }

        $hash = hash_init('sha256');
        $lineCount = 0;
        $fileSize = 0;

        try {
            foreach ($rows as $row) {
                $line = json_encode(
                    $row,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                )."\n";

                if (fwrite($stream, $line) === false) {
                    throw new RuntimeException('Unable to write a JSONL export line.');
                }

                hash_update($hash, $line);
                $lineCount++;
                $fileSize += strlen($line);
            }

            rewind($stream);
            $filesystem = Storage::disk($disk);

            if (! $filesystem->writeStream($temporaryPath, $stream)) {
                throw new RuntimeException("Unable to write export file [{$temporaryPath}].");
            }

            if (! $filesystem->move($temporaryPath, $path)) {
                throw new RuntimeException("Unable to finalize export file [{$path}].");
            }

            $result = new JsonlExportResult(
                fileKey: $fileKey,
                disk: $disk,
                path: $path,
                lineCount: $lineCount,
                checksum: hash_final($hash),
                fileSize: $fileSize,
            );

            $this->recordResult($snapshot, $basePath, $result);

            return $result;
        } finally {
            fclose($stream);
            Storage::disk($disk)->delete($temporaryPath);
        }
    }

    private function recordResult(
        CatalogSnapshot $snapshot,
        string $basePath,
        JsonlExportResult $result,
    ): void {
        $snapshot->refresh();
        $files = $snapshot->files_json ?? [];
        $files[$result->fileKey] = $result->toArray();

        $snapshot->forceFill([
            'storage_path' => $basePath,
            'files_json' => $files,
        ])->save();
    }
}
