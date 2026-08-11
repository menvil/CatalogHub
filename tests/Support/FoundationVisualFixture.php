<?php

declare(strict_types=1);

namespace Tests\Support;

use InvalidArgumentException;

final class FoundationVisualFixture
{
    /** @return array{code: string, name: string, domain: string} */
    public static function site(string $key): array
    {
        return match ($key) {
            'alpha' => [
                'code' => 'fixture-alpha',
                'name' => 'Fixture Alpha',
                'domain' => 'fixture-alpha.test',
            ],
            'beta' => [
                'code' => 'fixture-beta',
                'name' => 'Fixture Beta',
                'domain' => 'fixture-beta.test',
            ],
            'archived' => [
                'code' => 'fixture-archived',
                'name' => 'Fixture Archived',
                'domain' => 'fixture-archived.test',
            ],
            default => throw new InvalidArgumentException("Unknown foundation fixture [{$key}]."),
        };
    }

    /** @return array{name: string, email: string} */
    public static function centralAdmin(): array
    {
        return [
            'name' => 'Foundation Central Admin',
            'email' => 'central-admin@fixture.test',
        ];
    }
}
