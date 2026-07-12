<?php

namespace Tests\Feature\Admin;

use App\Models\CentralCatalog\CentralCategory;
use App\Models\Locale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTranslationEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_central_admin_to_save_category_translation(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $category = CentralCategory::factory()->create(['name' => 'Monitors']);
        $locale = Locale::factory()->create(['code' => 'de-DE']);

        $this->actingAs($admin)
            ->post(route('central.categories.translations.save', [$category, $locale]), [
                'name' => 'Monitore',
                'description' => 'Monitor-Kategorie',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('category_translations', [
            'category_id' => $category->id,
            'locale' => 'de-DE',
            'name' => 'Monitore',
        ]);
        $this->assertDatabaseHas('central_categories', [
            'id' => $category->id,
            'name' => 'Monitors',
        ]);
    }
}
