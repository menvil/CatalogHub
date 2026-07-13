<?php

namespace App\Services\Imports;

use JsonException;

final class RawPayloadHasher
{
    private const int DEFAULT_MAX_PAYLOAD_DEPTH = 64;

    /**
     * @param  array<array-key, mixed>  $payload
     *
     * @throws JsonException
     */
    public function hash(array $payload): string
    {
        return hash('sha256', $this->encode($payload));
    }

    /**
     * @param  array<array-key, mixed>  $payload
     *
     * @throws JsonException
     */
    public function encode(array $payload): string
    {
        $maxDepth = max(1, (int) config(
            'imports.serialized_php_max_depth',
            self::DEFAULT_MAX_PAYLOAD_DEPTH,
        ));

        // Reject recursive or excessively deep legacy payloads before traversing them.
        json_encode($payload, JSON_THROW_ON_ERROR, $maxDepth);

        return json_encode(
            $this->canonicalize($payload),
            JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            $maxDepth,
        );
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map($this->canonicalize(...), $value);
        }

        ksort($value, SORT_STRING);

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
