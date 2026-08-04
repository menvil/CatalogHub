<?php

namespace Tests\Feature\Public;

use Database\Seeders\Demo\MultiCategorySiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicInputValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_rejects_non_string_terms_before_controller_execution(): void
    {
        $this->get('http://tech-compare.test/en-US/search?q%5B0%5D=invalid')
            ->assertRedirect();
    }

    public function test_listing_rejects_out_of_range_pagination_before_controller_execution(): void
    {
        $this->get('http://tech-compare.test/en-US/categories/monitors/products?per_page=25')
            ->assertRedirect();
    }

    public function test_listing_rejects_nested_facet_input_before_controller_execution(): void
    {
        $this->get('http://tech-compare.test/en-US/categories/monitors/products?brand%5Bnested%5D%5B0%5D=invalid')
            ->assertRedirect();
    }

    public function test_compare_discards_nested_product_input_during_request_normalization(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);

        $this->get('http://tech-compare.test/en-US/compare?products%5B0%5D%5Bslug%5D=invalid')
            ->assertOk()
            ->assertDontSee('invalid');
    }
}
