<?php

declare(strict_types=1);

namespace App\Support\Imports;

use App\Support\Presentation\SafeExternalRecordUrl;
use Illuminate\Validation\ValidationException;

final class ExternalIdentityNormalizer
{
    public static function externalId(string $value): string
    {
        $controlCharacterMatch = preg_match('/\p{Cc}/u', $value);

        if ($controlCharacterMatch !== 0) {
            throw ValidationException::withMessages([
                'external_id' => 'The external ID must be nonblank, at most 255 characters, and contain no control characters.',
            ]);
        }

        $normalized = trim($value);

        if ($normalized === '' || mb_strlen($normalized) > 255) {
            throw ValidationException::withMessages([
                'external_id' => 'The external ID must be nonblank, at most 255 characters, and contain no control characters.',
            ]);
        }

        return $normalized;
    }

    public static function hash(string $normalizedExternalId): string
    {
        return hash('sha256', $normalizedExternalId);
    }

    public static function externalUrl(?string $value): ?string
    {
        $normalized = SafeExternalRecordUrl::normalize($value);

        if ($normalized !== null && ! SafeExternalRecordUrl::allows($normalized)) {
            throw ValidationException::withMessages([
                'external_url' => 'The external record URL must be an HTTP or HTTPS URL without embedded credentials.',
            ]);
        }

        return $normalized;
    }

    private function __construct() {}
}
