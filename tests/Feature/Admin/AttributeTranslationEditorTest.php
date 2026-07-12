<?php

namespace Tests\Feature\Admin;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\Locale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeTranslationEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_central_admin_to_save_attribute_translation(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $attribute = AttributeDefinition::factory()->create(['code' => 'refresh_rate']);
        $locale = Locale::factory()->create(['code' => 'de-DE']);

        $this->actingAs($admin)
            ->post(route('central.attributes.translations.save', [$attribute, $locale]), [
                'label' => 'Bildwiederholfrequenz',
                'help_text' => 'Maximale Frequenz des Panels.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attribute_translations', [
            'attribute_definition_id' => $attribute->id,
            'locale' => 'de-DE',
            'label' => 'Bildwiederholfrequenz',
        ]);
    }

    public function test_allows_central_admin_to_save_attribute_section_translation(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $section = AttributeSection::factory()->create(['code' => 'display']);
        $locale = Locale::factory()->create(['code' => 'de-DE']);

        $this->actingAs($admin)
            ->post(route('central.attribute-sections.translations.save', [$section, $locale]), [
                'name' => 'Bildschirm',
                'description' => 'Display details',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attribute_section_translations', [
            'attribute_section_id' => $section->id,
            'locale' => 'de-DE',
            'name' => 'Bildschirm',
        ]);
    }

    public function test_allows_central_admin_to_save_attribute_option_translation(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $option = AttributeOption::factory()->create(['code' => 'ips']);
        $locale = Locale::factory()->create(['code' => 'de-DE']);

        $this->actingAs($admin)
            ->post(route('central.attribute-options.translations.save', [$option, $locale]), [
                'label' => 'IPS',
                'description' => 'IPS panel',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attribute_option_translations', [
            'attribute_option_id' => $option->id,
            'locale' => 'de-DE',
            'label' => 'IPS',
        ]);
    }
}
