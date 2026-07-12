<?php

namespace Tests\Unit\Services;

use App\Models\CentralCatalog\CentralProduct;
use App\Services\Translations\TranslationSourceHashService;
use Tests\TestCase;

class TranslationSourceHashServiceTest extends TestCase
{
    public function test_generates_deterministic_source_hash_for_product(): void
    {
        $product = CentralProduct::factory()->make([
            'name' => 'LG Monitor',
            'model' => '27GP850-B',
        ]);

        $service = app(TranslationSourceHashService::class);

        $this->assertSame($service->forProduct($product), $service->forProduct($product));
    }

    public function test_changes_source_hash_when_source_text_changes(): void
    {
        $product = CentralProduct::factory()->make(['name' => 'Old Name']);

        $service = app(TranslationSourceHashService::class);
        $oldHash = $service->forProduct($product);

        $product->name = 'New Name';

        $this->assertNotSame($oldHash, $service->forProduct($product));
    }
}
