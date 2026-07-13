<?php

namespace App\Data\Imports;

final readonly class NormalizedAttributeValueData
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public bool $isValid,
        public mixed $value,
        public mixed $rawValue,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public array $metadata = [],
    ) {}

    /** @param array<string, mixed> $metadata */
    public static function success(mixed $value, mixed $rawValue, array $metadata = []): self
    {
        return new self(true, $value, $rawValue, metadata: $metadata);
    }

    /** @param array<string, mixed> $metadata */
    public static function failure(
        mixed $rawValue,
        string $errorCode,
        string $errorMessage,
        array $metadata = [],
    ): self {
        return new self(
            false,
            null,
            $rawValue,
            $errorCode,
            $errorMessage,
            $metadata,
        );
    }
}
