<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\SiteStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class SiteStatusTest extends TestCase
{
    public function test_registry_contains_exactly_the_four_foundation_statuses_and_labels(): void
    {
        self::assertSame(
            ['draft', 'active', 'suspended', 'archived'],
            array_column(SiteStatus::cases(), 'value'),
        );
        self::assertSame([
            'draft' => 'Draft',
            'active' => 'Active',
            'suspended' => 'Suspended',
            'archived' => 'Archived',
        ], SiteStatus::options());
    }

    #[DataProvider('availabilityMatrix')]
    public function test_public_and_administrative_boundaries_are_deterministic(
        SiteStatus $status,
        bool $publiclyAvailable,
        bool $allowsAdministration,
    ): void {
        self::assertSame($publiclyAvailable, $status->isPubliclyAvailable());
        self::assertSame($allowsAdministration, $status->allowsAdministration());
    }

    /** @return iterable<string, array{SiteStatus, bool, bool}> */
    public static function availabilityMatrix(): iterable
    {
        yield 'draft' => [SiteStatus::Draft, false, true];
        yield 'active' => [SiteStatus::Active, true, true];
        yield 'suspended' => [SiteStatus::Suspended, false, true];
        yield 'archived' => [SiteStatus::Archived, false, false];
    }
}
