<?php

declare(strict_types=1);

namespace Tests\Feature\Central;

use App\Enums\AuditAction;
use App\Enums\CentralCategoryStatus;
use App\Enums\CentralProductStatus;
use App\Enums\UserRole;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CatalogTag;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class CentralBrandClassificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_and_detail_expose_editable_tags_and_read_only_derived_coverage(): void
    {
        $route = Route::getRoutes()->getByName('central.brands.tags.update');
        self::assertNotNull($route);
        self::assertContains('PATCH', $route->methods());
        self::assertSame('admin/central/brands/{brand}/tags', $route->uri());
        self::assertContains('can:catalog.brands.manage', $route->gatherMiddleware());

        $brand = CentralBrand::factory()->create(['name' => 'Samsung']);
        $tag = CatalogTag::factory()->create(['name' => 'Premium']);
        $brand->tags()->attach($tag);
        $category = CentralCategory::factory()->create([
            'name' => 'Smartphones',
            'slug' => 'smartphones-classification',
            'status' => CentralCategoryStatus::Archived,
        ]);
        CentralProduct::factory()->count(2)->create([
            'central_brand_id' => $brand->id,
            'central_category_id' => $category->id,
            'status' => CentralProductStatus::Active,
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('central.brands.show', $brand))
            ->assertOk()
            ->assertSee('data-screen-region="classification"', false)
            ->assertSee('Premium')
            ->assertSee('Manage tags')
            ->assertSee('Current category coverage')
            ->assertSee('Derived automatically from direct Category assignments')
            ->assertSee('Smartphones')
            ->assertSee('Archived')
            ->assertSee('2')
            ->assertDontSee('Assign categories')
            ->assertDontSee('Manage Brand Categories');

        /** @var CentralBrand $viewBrand */
        $viewBrand = $response->viewData('brand');
        self::assertTrue($viewBrand->relationLoaded('tags'));
    }

    public function test_empty_classification_states_are_explicit_without_category_edit_controls(): void
    {
        $brand = CentralBrand::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('central.brands.show', $brand))
            ->assertOk()
            ->assertSee('No tags have been assigned to this Brand.')
            ->assertSee('No category coverage yet.')
            ->assertSee('Category coverage is derived automatically from Brand products.')
            ->assertDontSee('Assign categories');
    }

    public function test_http_save_uses_route_brand_normalizes_duplicates_and_preserves_request_audit_context(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $otherBrand = CentralBrand::factory()->create();

        $this->actingAs($actor)
            ->withHeader('X-Request-ID', 'brand-tags-request')
            ->patch(route('central.brands.tags.update', $brand), [
                'brand_id' => $otherBrand->id,
                'tags' => ['Premium', 'premium', ' Gaming '],
            ])
            ->assertRedirect(route('central.brands.show', $brand).'#classification')
            ->assertSessionHas('success', 'Brand tags updated.');

        self::assertSame(['Gaming', 'Premium'], $brand->fresh()->tags()->pluck('name')->all());
        self::assertTrue($otherBrand->fresh()->tags()->doesntExist());
        self::assertDatabaseCount('catalog_tags', 2);

        $audit = AuditLogEntry::query()->sole();
        self::assertSame(AuditAction::CatalogBrandTagsUpdated->value, $audit->action);
        self::assertSame($actor->id, $audit->actor_id);
        self::assertSame('central', $audit->context);
        self::assertNull($audit->site_id);
        self::assertSame($brand->getMorphClass(), $audit->subject_type);
        self::assertSame((string) $brand->id, $audit->subject_id);
        self::assertSame(['tags' => []], $audit->before_json);
        self::assertSame(['tags' => ['Gaming', 'Premium']], $audit->after_json);
        self::assertSame('brand-tags-request', $audit->request_id);
    }

    public function test_empty_submission_clears_tags_and_archived_brand_remains_manageable(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->archived()->create();
        $tag = CatalogTag::factory()->create(['name' => 'Premium']);
        $brand->tags()->attach($tag);

        $this->actingAs($actor)
            ->patch(route('central.brands.tags.update', $brand), [])
            ->assertRedirect(route('central.brands.show', $brand).'#classification');

        self::assertTrue($brand->fresh()->tags()->doesntExist());
        self::assertSame('archived', $brand->fresh()->status->value);
        self::assertDatabaseCount('catalog_tags', 1);
        self::assertDatabaseCount('audit_log_entries', 1);
    }

    public function test_invalid_and_unauthorized_requests_make_no_changes_or_audit_rows(): void
    {
        $brand = CentralBrand::factory()->create();
        $persistedTag = CatalogTag::factory()->create(['name' => 'Premium']);
        $brand->tags()->attach($persistedTag);
        $translator = User::factory()->create(['role' => UserRole::Translator]);

        $this->actingAs($translator)
            ->patch(route('central.brands.tags.update', $brand), ['tags' => ['Forbidden']])
            ->assertForbidden();

        $manager = User::factory()->create();
        $invalidResponse = $this->actingAs($manager)
            ->from(route('central.brands.show', $brand))
            ->patch(route('central.brands.tags.update', $brand), ['tags' => ["Line\nBreak"]])
            ->assertRedirect(route('central.brands.show', $brand))
            ->assertSessionHasErrors('tags.0')
            ->assertSessionHasInput('tags', ["Line\nBreak"]);

        $this->followRedirects($invalidResponse)
            ->assertOk()
            ->assertSee('data-admin-modal="manage-brand-tags-modal"', false)
            ->assertSee('data-admin-modal-open="true"', false)
            ->assertSee('data-ui-tag-input-reset-value data-tag-name="Premium"', false)
            ->assertSee('contain no control characters or newlines.');

        $this->actingAs($manager)
            ->patch(route('central.brands.tags.update', $brand), [
                'tags' => array_map(static fn (int $index): string => "Tag {$index}", range(1, 21)),
            ])
            ->assertSessionHasErrors('tags');

        self::assertDatabaseCount('catalog_tags', 1);
        self::assertDatabaseCount('central_brand_tag', 1);
        self::assertDatabaseCount('audit_log_entries', 0);

    }
}
