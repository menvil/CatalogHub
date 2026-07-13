<?php

namespace Tests\Feature\Actions;

use App\Actions\Imports\ApproveNormalizedProductDraftAction;
use App\Actions\Imports\PublishNormalizedProductDraftToCentralAction;
use App\Enums\AttributeDataType;
use App\Enums\CentralProductStatus;
use App\Enums\UserRole;
use App\Filament\Resources\NormalizedProductDraftResource\Pages\ViewNormalizedProductDraft;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class PublishNormalizedProductDraftToCentralActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_draft_publishes_product_attributes_and_media_transactionally(): void
    {
        $this->assertTrue(Schema::hasColumn('normalized_product_drafts', 'published_central_product_id'));
        $editor = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $brand = CentralBrand::factory()->create();
        $category = CentralCategory::factory()->create();
        $power = AttributeDefinition::factory()->for($category, 'category')->create([
            'code' => 'power',
            'data_type' => AttributeDataType::Decimal,
            'dimension' => 'power',
            'canonical_unit' => 'watt',
        ]);
        $portable = AttributeDefinition::factory()->for($category, 'category')->create([
            'code' => 'portable',
            'data_type' => AttributeDataType::Boolean,
        ]);
        $asset = MediaAsset::factory()->create();
        $draft = NormalizedProductDraft::factory()->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Published Mixer',
            'slug' => 'published-mixer',
            'normalized_payload_json' => ['model' => 'MX-500'],
            'attributes_json' => [
                [
                    'attribute_definition_id' => $power->id,
                    'raw_value' => '500 W',
                    'value_type' => 'decimal',
                    'value' => 500,
                    'metadata' => [
                        'source_unit' => 'watt',
                        'canonical_value' => 500,
                        'canonical_unit' => 'watt',
                    ],
                    'confidence' => '0.9500',
                ],
                [
                    'attribute_definition_id' => $portable->id,
                    'raw_value' => 'yes',
                    'value_type' => 'boolean',
                    'value' => true,
                ],
            ],
            'media_json' => [[
                'media_asset_id' => $asset->id,
                'status' => 'downloaded',
                'role' => 'main',
            ]],
            'status' => 'pending_review',
        ]);
        $draft = app(ApproveNormalizedProductDraftAction::class)->handle($draft, $editor);

        $product = app(PublishNormalizedProductDraftToCentralAction::class)->handle($draft, $editor);

        $this->assertSame('Published Mixer', $product->name);
        $this->assertSame('MX-500', $product->model);
        $this->assertSame($brand->id, $product->central_brand_id);
        $this->assertSame($category->id, $product->central_category_id);
        $this->assertSame(CentralProductStatus::Draft, $product->status);
        $this->assertSame(2, CentralProductAttributeValue::query()->where('central_product_id', $product->id)->count());
        $this->assertDatabaseHas('central_product_attribute_values', [
            'central_product_id' => $product->id,
            'attribute_definition_id' => $power->id,
            'value_type' => 'decimal',
            'value_number' => 500,
            'canonical_unit' => 'watt',
        ]);
        $this->assertDatabaseHas('media_assignments', [
            'media_asset_id' => $asset->id,
            'entity_type' => 'central_product',
            'entity_id' => $product->id,
            'role' => 'main',
            'is_primary' => true,
        ]);
        $draft = $draft->fresh();
        $this->assertSame('published', $draft->status);
        $this->assertSame($product->id, $draft->published_central_product_id);
    }

    public function test_matched_product_is_updated_instead_of_creating_another_product(): void
    {
        $editor = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $product = CentralProduct::factory()->create(['name' => 'Old title']);
        $draft = NormalizedProductDraft::factory()->create([
            'matched_central_product_id' => $product->id,
            'title' => 'Updated imported title',
            'status' => 'pending_review',
        ]);
        $draft = app(ApproveNormalizedProductDraftAction::class)->handle($draft, $editor);

        $published = app(PublishNormalizedProductDraftToCentralAction::class)->handle($draft, $editor);

        $this->assertSame($product->id, $published->id);
        $this->assertSame('Updated imported title', $published->name);
        $this->assertSame(1, CentralProduct::query()->count());
    }

    public function test_matched_product_resolves_code_attribute_from_its_preserved_category(): void
    {
        $editor = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $category = CentralCategory::factory()->create();
        $definition = AttributeDefinition::factory()->for($category, 'category')->create([
            'code' => 'material',
            'data_type' => AttributeDataType::String,
        ]);
        $product = CentralProduct::factory()->for($category, 'category')->create();
        $draft = NormalizedProductDraft::factory()->create([
            'matched_central_product_id' => $product->id,
            'category_id' => null,
            'status' => 'approved',
            'approved_by_user_id' => $editor->id,
            'approved_at' => now(),
            'attributes_json' => [[
                'code' => 'material',
                'value_type' => 'string',
                'value' => 'Steel',
            ]],
        ]);

        app(PublishNormalizedProductDraftToCentralAction::class)->handle($draft, $editor);

        $this->assertDatabaseHas('central_product_attribute_values', [
            'central_product_id' => $product->id,
            'attribute_definition_id' => $definition->id,
            'value_text' => 'Steel',
        ]);
    }

    public function test_unapproved_or_rejected_draft_cannot_be_published(): void
    {
        $editor = User::factory()->create(['role' => UserRole::CatalogEditor]);

        foreach (['pending_review', 'rejected'] as $status) {
            $draft = NormalizedProductDraft::factory()->create(['status' => $status]);

            try {
                app(PublishNormalizedProductDraftToCentralAction::class)->handle($draft, $editor);
                $this->fail("Draft status [{$status}] was published.");
            } catch (LogicException) {
                $this->assertSame($status, $draft->fresh()->status);
            }
        }

        $this->assertSame(0, CentralProduct::query()->count());
    }

    public function test_failure_rolls_back_partially_created_product(): void
    {
        $editor = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $draft = NormalizedProductDraft::factory()->create([
            'status' => 'approved',
            'approved_by_user_id' => $editor->id,
            'approved_at' => now(),
            'attributes_json' => [[
                'attribute_definition_id' => 999999,
                'value_type' => 'string',
                'value' => 'invalid',
            ]],
        ]);

        try {
            app(PublishNormalizedProductDraftToCentralAction::class)->handle($draft, $editor);
            $this->fail('Invalid attribute did not abort publishing.');
        } catch (LogicException) {
            $this->assertSame(0, CentralProduct::query()->count());
            $this->assertSame('approved', $draft->fresh()->status);
            $this->assertSame(0, MediaAssignment::query()->count());
        }
    }

    public function test_malformed_attribute_without_identifier_throws_domain_exception(): void
    {
        $editor = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $draft = NormalizedProductDraft::factory()->create([
            'status' => 'approved',
            'approved_by_user_id' => $editor->id,
            'approved_at' => now(),
            'attributes_json' => [[
                'value_type' => 'string',
                'value' => 'missing definition',
            ]],
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('references an unknown attribute definition');

        app(PublishNormalizedProductDraftToCentralAction::class)->handle($draft, $editor);
    }

    public function test_duplicate_attribute_candidates_abort_publication(): void
    {
        $editor = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $category = CentralCategory::factory()->create();
        $definition = AttributeDefinition::factory()->for($category, 'category')->create([
            'data_type' => AttributeDataType::String,
        ]);
        $candidate = [
            'attribute_definition_id' => $definition->id,
            'value_type' => 'string',
            'value' => 'Steel',
        ];
        $draft = NormalizedProductDraft::factory()->create([
            'category_id' => $category->id,
            'status' => 'approved',
            'approved_by_user_id' => $editor->id,
            'approved_at' => now(),
            'attributes_json' => [$candidate, $candidate],
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('contains duplicate candidates');

        app(PublishNormalizedProductDraftToCentralAction::class)->handle($draft, $editor);
    }

    public function test_candidate_cannot_override_attribute_definition_type(): void
    {
        $editor = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $category = CentralCategory::factory()->create();
        $definition = AttributeDefinition::factory()->for($category, 'category')->create([
            'data_type' => AttributeDataType::Boolean,
        ]);
        $draft = NormalizedProductDraft::factory()->create([
            'category_id' => $category->id,
            'status' => 'approved',
            'approved_by_user_id' => $editor->id,
            'approved_at' => now(),
            'attributes_json' => [[
                'attribute_definition_id' => $definition->id,
                'value_type' => 'string',
                'value' => 'yes',
            ]],
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('expects value type [boolean]');

        app(PublishNormalizedProductDraftToCentralAction::class)->handle($draft, $editor);
    }

    public function test_ui_calls_guarded_backend_publish_action(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $editor = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $draft = NormalizedProductDraft::factory()->create([
            'status' => 'approved',
            'approved_by_user_id' => $editor->id,
            'approved_at' => now(),
        ]);

        try {
            app(PublishNormalizedProductDraftToCentralAction::class)->handle($draft, $moderator);
            $this->fail('Moderator publish was not blocked.');
        } catch (AuthorizationException) {
            $this->assertSame(0, CentralProduct::query()->count());
        }

        Livewire::actingAs($editor)
            ->test(ViewNormalizedProductDraft::class, ['record' => $draft->id])
            ->callAction('publish');

        $this->assertSame('published', $draft->fresh()->status);
        $this->assertSame(1, CentralProduct::query()->count());
    }
}
