<?php

namespace Tests\Feature\Admin;

use App\Enums\TranslationStatus;
use App\Enums\UserRole;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTranslationEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_central_admin_to_save_product_translation(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $product = CentralProduct::factory()->create(['name' => 'LG Monitor']);
        $locale = Locale::factory()->create(['code' => 'de-DE']);

        $this->actingAs($admin)
            ->post(route('central.products.translations.save', [$product, $locale]), [
                'name' => 'LG Monitor DE',
                'short_description' => 'Gaming-Monitor',
                'description' => 'Beschreibung',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('product_translations', [
            'product_id' => $product->id,
            'locale' => 'de-DE',
            'name' => 'LG Monitor DE',
        ]);
        $this->assertDatabaseHas('central_products', [
            'id' => $product->id,
            'name' => 'LG Monitor',
        ]);
    }

    public function test_blocks_users_without_translation_permission_from_product_translation_editor(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $product = CentralProduct::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);

        $this->actingAs($moderator)
            ->get(route('central.products.translations.edit', [$product, $locale]))
            ->assertForbidden();

        $this->actingAs($moderator)
            ->post(route('central.products.translations.save', [$product, $locale]), [
                'name' => 'Unauthorized',
            ])
            ->assertForbidden();
    }

    public function test_rejects_direct_approved_status_in_product_translation_save(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $product = CentralProduct::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);

        $this->actingAs($admin)
            ->from(route('central.products.translations.edit', [$product, $locale]))
            ->post(route('central.products.translations.save', [$product, $locale]), [
                'name' => 'LG Monitor DE',
                'status' => TranslationStatus::Approved->value,
            ])
            ->assertRedirect(route('central.products.translations.edit', [$product, $locale]))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseMissing('product_translations', [
            'product_id' => $product->id,
            'locale' => 'de-DE',
            'status' => TranslationStatus::Approved->value,
        ]);
    }

    public function test_validates_product_translation_payload(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $product = CentralProduct::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);

        $this->actingAs($admin)
            ->from(route('central.products.translations.edit', [$product, $locale]))
            ->post(route('central.products.translations.save', [$product, $locale]), [
                'name' => str_repeat('x', 256),
                'status' => 'not-a-status',
            ])
            ->assertRedirect(route('central.products.translations.edit', [$product, $locale]))
            ->assertSessionHasErrors(['name', 'status']);
    }
}
