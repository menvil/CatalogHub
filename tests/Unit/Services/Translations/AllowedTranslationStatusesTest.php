<?php

namespace Tests\Unit\Services\Translations;

use App\Enums\TranslationStatus;
use App\Models\Translations\ProductTranslation;
use App\Services\Translations\AllowedTranslationStatuses;
use PHPUnit\Framework\TestCase;

class AllowedTranslationStatusesTest extends TestCase
{
    public function test_new_or_unapproved_translation_cannot_be_directly_approved(): void
    {
        $statuses = new AllowedTranslationStatuses;
        $translation = new ProductTranslation(['status' => TranslationStatus::HumanReviewed]);

        $this->assertNotContains(TranslationStatus::Approved->value, $statuses->for(null));
        $this->assertNotContains(TranslationStatus::Approved->value, $statuses->for($translation));
    }

    public function test_existing_approved_translation_can_preserve_approved_status(): void
    {
        $statuses = new AllowedTranslationStatuses;
        $translation = new ProductTranslation(['status' => TranslationStatus::Approved]);

        $this->assertContains(TranslationStatus::Approved->value, $statuses->for($translation));
    }
}
