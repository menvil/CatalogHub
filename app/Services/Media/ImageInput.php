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
        $bytes = file_get_contents($file->getRealPath());

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
