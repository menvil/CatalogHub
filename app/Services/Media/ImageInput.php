<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;

/** Raw, untrusted image input. */
final readonly class ImageInput
{
    public function __construct(
        public string $bytes,
        public ?string $originalFilename,
    ) {}

    public static function fromUploadedFile(UploadedFile $file): self
    {
        $path = $file->getRealPath();
        $size = $file->getSize();
        if (! is_string($path) || ! is_file($path) || ! is_int($size) || $size < 0 || $size > (int) config('media.max_upload_bytes')) {
            throw new ImageIngestException('The uploaded image could not be read.');
        }

        $bytes = file_get_contents($path);

        if ($bytes === false) {
            throw new ImageIngestException('The uploaded image could not be read.');
        }

        return new self($bytes, $file->getClientOriginalName());
    }

    public function byteSize(): int
    {
        return strlen($this->bytes);
    }
}
