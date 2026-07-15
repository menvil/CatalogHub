<?php

namespace App\Services\Pricing;

use App\Enums\PriceSourceCredentialStatus;
use App\Models\PriceSource;
use App\Models\PriceSourceCredential;
use Illuminate\Support\Facades\Crypt;
use JsonException;
use RuntimeException;

final class PriceSourceCredentialService
{
    /** @param array<string, mixed> $credentials */
    public function store(PriceSource $source, array $credentials): PriceSourceCredential
    {
        try {
            $serialized = json_encode($credentials, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Price source credentials cannot be serialized.', previous: $exception);
        }

        $record = $source->credentials()->updateOrCreate([], [
            'encrypted_credentials_json' => Crypt::encryptString($serialized),
            'status' => PriceSourceCredentialStatus::Active,
            'last_rotated_at' => now(),
        ]);

        $source->unsetRelation('credentials');

        return $record;
    }

    /** @return array<string, mixed> */
    public function resolve(PriceSource $source): array
    {
        $record = $source->credentials()->first();

        if ($record === null) {
            throw new RuntimeException("Price source [{$source->getKey()}] has no credentials.");
        }

        try {
            $credentials = json_decode(
                Crypt::decryptString($record->getRawOriginal('encrypted_credentials_json')),
                true,
                flags: JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new RuntimeException('Decrypted price source credentials are invalid JSON.', previous: $exception);
        }

        if (! is_array($credentials)) {
            throw new RuntimeException('Decrypted price source credentials must be an object.');
        }

        return $credentials;
    }

    /** @return array<string, mixed> */
    public function mask(PriceSource $source): array
    {
        return $this->maskValues($this->resolve($source));
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function maskValues(array $values): array
    {
        return collect($values)->map(function (mixed $value): mixed {
            if (is_array($value)) {
                return $this->maskValues($value);
            }

            if (! is_scalar($value) || is_bool($value)) {
                return '****';
            }

            $value = (string) $value;

            return '****'.substr($value, -4);
        })->all();
    }
}
