<?php

namespace App\Services\Export;

final class SafeConfigSanitizer
{
    private const SENSITIVE_KEY_FRAGMENTS = [
        'secret',
        'password',
        'token',
        'credential',
        'api_key',
        'private_key',
        'access_key',
    ];

    /** @param array<array-key, mixed>|null $config */
    public function sanitize(?array $config): array
    {
        $safe = [];

        foreach ($config ?? [] as $key => $value) {
            if (is_string($key) && $this->isSensitiveKey($key)) {
                continue;
            }

            $safe[$key] = is_array($value) ? $this->sanitize($value) : $value;
        }

        return $safe;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(str_replace(['-', ' '], '_', $key));

        foreach (self::SENSITIVE_KEY_FRAGMENTS as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
