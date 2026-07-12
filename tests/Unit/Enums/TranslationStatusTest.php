<?php

namespace Tests\Unit\Enums;

use App\Enums\TranslationStatus;
use Tests\TestCase;

class TranslationStatusTest extends TestCase
{
    public function test_has_translation_status_enum_values(): void
    {
        $this->assertSame('missing', TranslationStatus::Missing->value);
        $this->assertSame('machine_translated', TranslationStatus::MachineTranslated->value);
        $this->assertSame('human_reviewed', TranslationStatus::HumanReviewed->value);
        $this->assertSame('approved', TranslationStatus::Approved->value);
        $this->assertSame('outdated', TranslationStatus::Outdated->value);
    }
}
