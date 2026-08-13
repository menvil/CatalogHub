<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\CentralBrandStatus;
use Tests\TestCase;

final class CentralBrandStatusTest extends TestCase
{
    public function test_values_and_default_match_the_brand_lifecycle(): void
    {
        self::assertSame(
            ['draft', 'active', 'archived'],
            array_column(CentralBrandStatus::cases(), 'value'),
        );
        self::assertSame(CentralBrandStatus::Draft, CentralBrandStatus::default());
    }

    public function test_labels_and_colors_match_the_existing_status_helpers_contract(): void
    {
        self::assertSame([
            'draft' => 'Draft',
            'active' => 'Active',
            'archived' => 'Archived',
        ], CentralBrandStatus::options());
        self::assertSame('gray', CentralBrandStatus::Draft->color());
        self::assertSame('success', CentralBrandStatus::Active->color());
        self::assertSame('danger', CentralBrandStatus::Archived->color());
    }
}
