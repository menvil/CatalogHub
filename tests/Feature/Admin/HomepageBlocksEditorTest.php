<?php

namespace Tests\Feature\Admin;

use App\Domains\Themes\Actions\AddSiteHomeBlockAction;
use App\Domains\Themes\Actions\ReorderSiteHomeBlocksAction;
use App\Domains\Themes\Actions\ToggleSiteHomeBlockAction;
use App\Domains\Themes\Services\BlockConfigValidator;
use App\Enums\BlockStatus;
use App\Enums\ThemeStatus;
use App\Enums\UserRole;
use App\Exceptions\Themes\CannotUseBlockException;
use App\Filament\Resources\SiteResource\Pages\HomepageBlocksEditor;
use App\Models\BlockDefinition;
use App\Models\Site;
use App\Models\SiteHomeBlock;
use App\Models\Theme;
use App\Models\ThemeManifestRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class HomepageBlocksEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_admin_can_open_editor_and_add_compatible_block(): void
    {
        [$site, $block] = $this->siteAndBlock();

        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(HomepageBlocksEditor::class, ['record' => $site->getRouteKey()])
            ->assertSee('Hero Search')
            ->set('selectedBlockCode', $block->code)
            ->set('addConfigJson', '{"title":"Find products"}')
            ->call('add')
            ->assertNotified('Homepage block added');

        $this->assertDatabaseHas('site_home_blocks', [
            'site_id' => $site->id,
            'block_code' => 'hero_search',
            'position' => 1,
            'enabled' => true,
        ]);
    }

    public function test_incompatible_block_cannot_be_added(): void
    {
        [$site] = $this->siteAndBlock(themeSupports: []);

        $this->expectException(CannotUseBlockException::class);
        app(AddSiteHomeBlockAction::class)->handle($site, 'hero_search');
    }

    public function test_invalid_block_config_is_rejected(): void
    {
        [$site] = $this->siteAndBlock();

        $this->expectException(ValidationException::class);
        app(AddSiteHomeBlockAction::class)->handle($site, 'hero_search', ['title' => 42]);
    }

    public function test_add_and_edit_configuration_drafts_are_independent(): void
    {
        [$site, $definition] = $this->siteAndBlock();
        $existing = SiteHomeBlock::factory()->create([
            'site_id' => $site->id,
            'block_code' => $definition->code,
            'position' => 1,
            'config_json' => ['title' => 'Existing title'],
        ]);

        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(HomepageBlocksEditor::class, ['record' => $site->getRouteKey()])
            ->set('selectedBlockCode', $definition->code)
            ->set('addConfigJson', '{"title":"New block title"}')
            ->call('edit', $existing->id)
            ->assertSet('addConfigJson', '{"title":"New block title"}')
            ->assertSet('editConfigJson', json_encode(['title' => 'Existing title'], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))
            ->call('add')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('site_home_blocks', [
            'site_id' => $site->id,
            'position' => 2,
            'config_json' => json_encode(['title' => 'New block title'], JSON_THROW_ON_ERROR),
        ]);
    }

    public function test_nested_config_errors_are_mapped_to_each_json_editor(): void
    {
        [$site, $definition] = $this->siteAndBlock();
        $existing = SiteHomeBlock::factory()->create([
            'site_id' => $site->id,
            'block_code' => $definition->code,
            'position' => 1,
        ]);

        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(HomepageBlocksEditor::class, ['record' => $site->getRouteKey()])
            ->set('selectedBlockCode', $definition->code)
            ->set('addConfigJson', '{"title":42}')
            ->call('add')
            ->assertHasErrors(['addConfigJson'])
            ->call('edit', $existing->id)
            ->set('editConfigJson', '{"title":42}')
            ->call('saveConfig')
            ->assertHasErrors(['editConfigJson']);
    }

    public function test_empty_object_is_accepted_and_json_list_is_rejected(): void
    {
        [$site, $definition] = $this->siteAndBlock();

        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(HomepageBlocksEditor::class, ['record' => $site->getRouteKey()])
            ->set('selectedBlockCode', $definition->code)
            ->set('addConfigJson', '{}')
            ->call('add')
            ->assertHasNoErrors()
            ->set('selectedBlockCode', $definition->code)
            ->set('addConfigJson', '[]')
            ->call('add')
            ->assertHasErrors(['addConfigJson']);
    }

    public function test_null_is_rejected_unless_schema_explicitly_allows_it(): void
    {
        [, $definition] = $this->siteAndBlock();
        $definition->update([
            'config_schema_json' => [
                'title' => 'string',
                'category_id' => ['type' => 'integer', 'nullable' => true],
            ],
        ]);
        $validator = app(BlockConfigValidator::class);

        try {
            $validator->validate($definition->fresh(), ['title' => null]);
            $this->fail('A non-nullable string accepted null.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('config.title', $exception->errors());
        }

        $validator->validate($definition->fresh(), ['category_id' => null]);
        $this->addToAssertionCount(1);
    }

    public function test_blocks_can_be_toggled_and_reordered(): void
    {
        [$site, $firstDefinition] = $this->siteAndBlock(['hero_search', 'top_products']);
        $secondDefinition = BlockDefinition::factory()->create([
            'code' => 'top_products',
            'name' => 'Top Products',
            'status' => BlockStatus::Active,
            'supported_page_types_json' => ['home'],
        ]);
        $first = SiteHomeBlock::factory()->create(['site_id' => $site->id, 'block_code' => $firstDefinition->code, 'position' => 1]);
        $second = SiteHomeBlock::factory()->create(['site_id' => $site->id, 'block_code' => $secondDefinition->code, 'position' => 2]);

        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(HomepageBlocksEditor::class, ['record' => $site->getRouteKey()])
            ->call('toggle', $first->id)
            ->call('move', $second->id, 'up');

        $this->assertFalse($first->fresh()->enabled);
        $this->assertSame(1, $second->fresh()->position);
        $this->assertSame(2, $first->fresh()->position);

        app(ReorderSiteHomeBlocksAction::class)->handle($site, [$first->id, $second->id]);
        $this->assertSame(1, $first->fresh()->position);
    }

    public function test_incompatible_disabled_block_cannot_be_reenabled(): void
    {
        [$site, $definition] = $this->siteAndBlock();
        $block = SiteHomeBlock::factory()->create([
            'site_id' => $site->id,
            'block_code' => $definition->code,
            'position' => 1,
            'enabled' => false,
        ]);
        $site->theme->manifest()->update([
            'manifest_json' => ['code' => $site->theme->code, 'name' => $site->theme->name, 'supports' => [], 'layouts' => ['home' => 'home-clean']],
            'supports_json' => [],
        ]);

        $this->expectException(CannotUseBlockException::class);
        app(ToggleSiteHomeBlockAction::class)->handle($site, $block);
    }

    public function test_reorder_staging_is_derived_above_existing_positions(): void
    {
        [$site, $firstDefinition] = $this->siteAndBlock(['hero_search', 'top_products']);
        $secondDefinition = BlockDefinition::factory()->create([
            'code' => 'top_products',
            'status' => BlockStatus::Active,
            'supported_page_types_json' => ['home'],
        ]);
        $first = SiteHomeBlock::factory()->create(['site_id' => $site->id, 'block_code' => $firstDefinition->code, 'position' => 1]);
        $second = SiteHomeBlock::factory()->create(['site_id' => $site->id, 'block_code' => $secondDefinition->code, 'position' => 1_000_000]);

        app(ReorderSiteHomeBlocksAction::class)->handle($site, [$second->id, $first->id]);

        $this->assertSame(1, $second->fresh()->position);
        $this->assertSame(2, $first->fresh()->position);
    }

    public function test_user_without_site_content_permission_cannot_open_editor(): void
    {
        $site = Site::factory()->create();

        $this->actingAs(User::factory()->create(['role' => UserRole::CatalogEditor]))
            ->get(HomepageBlocksEditor::getUrl(['record' => $site]))
            ->assertForbidden();
    }

    /**
     * @param  list<string>  $themeSupports
     * @return array{Site, BlockDefinition}
     */
    private function siteAndBlock(array $themeSupports = ['hero_search']): array
    {
        $theme = Theme::factory()->create(['status' => ThemeStatus::Active]);
        ThemeManifestRecord::query()->create([
            'theme_id' => $theme->id,
            'manifest_json' => ['code' => $theme->code, 'name' => $theme->name, 'supports' => $themeSupports, 'layouts' => ['home' => 'home-clean']],
            'supports_json' => $themeSupports,
            'layouts_json' => ['home' => 'home-clean'],
        ]);
        $site = Site::factory()->create(['theme_id' => $theme->id]);
        $block = BlockDefinition::factory()->create([
            'code' => 'hero_search',
            'name' => 'Hero Search',
            'status' => BlockStatus::Active,
            'supported_page_types_json' => ['home'],
            'config_schema_json' => ['title' => 'string'],
        ]);

        return [$site, $block];
    }
}
