<?php

namespace App\Services\Export;

final readonly class JsonlExportResult
{
    public function __construct(
        public string $fileKey,
        public string $disk,
        public string $path,
        public int $lineCount,
        public string $checksum,
        public int $fileSize,
    ) {}

    /** @return array{disk: string, path: string, line_count: int, checksum: string, file_size: int} */
    public function toArray(): array
    {
        return [
            'disk' => $this->disk,
            'path' => $this->path,
            'line_count' => $this->lineCount,
            'checksum' => $this->checksum,
            'file_size' => $this->fileSize,
        ];
    }
}
