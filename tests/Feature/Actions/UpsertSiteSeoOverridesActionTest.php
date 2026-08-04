<?php

namespace Tests\Feature\Actions;

use App\Actions\Sites\UpsertSiteSeoOverridesAction;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\EnablesSiteLocales;
use Tests\Concerns\EnablesSiteProductCategories;
use Tests\TestCase;

final class UpsertSiteSeoOverridesActionTest extends TestCase
{
    use EnablesSiteLocales;
    use EnablesSiteProductCategories;
    use RefreshDatabase;

    public function test_it_saves_all_seo_fields_as_one_use_case(): void
    {
        [$site, $product] = $this->configuredProduct();

        app(UpsertSiteSeoOverridesAction::class)->handle(
            $site,
            'product',
            $product->id,
            'de-DE',
            'Title',
            'Description',
            'Introduction',
        );

        foreach (['meta_title', 'meta_description', 'intro_text'] as $field) {
            $this->assertDatabaseHas('site_overrides', [
                'site_id' => $site->id,
                'entity_id' => $product->id,
                'locale_code' => 'de-DE',
                'field' => $field,
            ]);
        }
    }

    public function test_it_rolls_back_all_fields_when_one_override_is_invalid(): void
    {
        [$site, $product] = $this->configuredProduct();

        try {
            app(UpsertSiteSeoOverridesAction::class)->handle(
                $site,
                'product',
                $product->id,
                'de-DE',
                'This must be rolled back',
                str_repeat('x', 1001),
                'This must not be written',
            );
            $this->fail('Expected the invalid description to fail validation.');
        } catch (ValidationException) {
            $this->assertDatabaseMissing('site_overrides', [
                'site_id' => $site->id,
                'entity_id' => $product->id,
            ]);
        }
    }

    /** @return array{Site, CentralProduct} */
    private function configuredProduct(): array
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $this->enableProductCategory($site, $product);
        $this->enableLocale($site, 'de-DE');

        return [$site, $product];
    }
}
