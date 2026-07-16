<?php

namespace App\Rules\Media;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

final readonly class ValidMediaDimensions implements ValidationRule
{
    public function __construct(
        private int $maxWidth,
        private int $maxHeight,
        private int $maxPixels,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $dimensions = @getimagesize($value->getRealPath());

        if ($dimensions === false) {
            $fail('The uploaded image dimensions could not be read.');

            return;
        }

        [$width, $height] = [(int) $dimensions[0], (int) $dimensions[1]];

        if ($width > $this->maxWidth || $height > $this->maxHeight) {
            $fail('The uploaded image dimensions are too large.');
        }

        if ($width * $height > $this->maxPixels) {
            $fail('The uploaded image has too many pixels.');
        }
    }
}
