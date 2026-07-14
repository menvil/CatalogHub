<?php

namespace Tests\Unit\Enums;

use App\Enums\ContentType;
use App\Models\ContentItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_expected_content_types_and_labels_exist(): void
    {
        $this->assertSame('article', ContentType::Article->value);
        $this->assertSame('buying_guide', ContentType::BuyingGuide->value);
        $this->assertSame('faq', ContentType::Faq->value);
        $this->assertCount(7, ContentType::cases());
        $this->assertSame('Buying guide', ContentType::BuyingGuide->label());
    }

    public function test_content_item_type_casts_to_enum(): void
    {
        $item = ContentItem::factory()->create(['type' => ContentType::HowToGuide]);

        $this->assertSame(ContentType::HowToGuide, $item->type);
    }
}
