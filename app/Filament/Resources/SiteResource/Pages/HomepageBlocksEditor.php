<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Domains\Themes\Actions\AddSiteHomeBlockAction;
use App\Domains\Themes\Actions\ReorderSiteHomeBlocksAction;
use App\Domains\Themes\Actions\ToggleSiteHomeBlockAction;
use App\Domains\Themes\Actions\UpdateSiteHomeBlockConfigAction;
use App\Domains\Themes\Services\BlockCompatibilityValidator;
use App\Domains\Themes\Services\BlockRegistry;
use App\Exceptions\Themes\CannotUseBlockException;
use App\Filament\Resources\SiteResource;
use App\Models\BlockDefinition;
use App\Models\Site;
use App\Models\SiteHomeBlock;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final class HomepageBlocksEditor extends Page
{
    use InteractsWithRecord;

    protected static string $resource = SiteResource::class;

    protected string $view = 'filament.resources.site-resource.pages.homepage-blocks-editor';

    protected static ?string $title = 'Homepage Blocks Editor';

    public string $selectedBlockCode = '';

    public string $configJson = '{}';

    public ?int $editingBlockId = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    /** @param array<string, mixed> $parameters */
    public static function canAccess(array $parameters = []): bool
    {
        return parent::canAccess($parameters) && SiteResource::canManageContent();
    }

    /** @return Collection<int, SiteHomeBlock> */
    public function getHomeBlocks(): Collection
    {
        /** @var Site $site */
        $site = $this->getRecord();

        return $site->homeBlocks()->with('definition')->orderBy('position')->get();
    }

    /** @return list<array{block: BlockDefinition, compatible: bool, reason: string|null}> */
    public function getAvailableBlocks(): array
    {
        /** @var Site $site */
        $site = $this->getRecord();
        $validator = app(BlockCompatibilityValidator::class);

        return app(BlockRegistry::class)->forPageType('home')->map(function (BlockDefinition $block) use ($site, $validator): array {
            try {
                $validator->validate($site, $block->code);

                return ['block' => $block, 'compatible' => true, 'reason' => null];
            } catch (CannotUseBlockException $exception) {
                return ['block' => $block, 'compatible' => false, 'reason' => $exception->getMessage()];
            }
        })->values()->all();
    }

    public function add(AddSiteHomeBlockAction $action): void
    {
        $this->validate(['selectedBlockCode' => ['required', 'string']]);
        /** @var Site $site */
        $site = $this->getRecord();
        $action->handle($site, $this->selectedBlockCode, $this->decodedConfig());
        $this->reset('selectedBlockCode', 'configJson');
        $this->configJson = '{}';
        Notification::make()->title('Homepage block added')->success()->send();
    }

    public function edit(int $homeBlockId): void
    {
        /** @var Site $site */
        $site = $this->getRecord();
        $block = $site->homeBlocks()->findOrFail($homeBlockId);
        $this->editingBlockId = $block->id;
        $this->configJson = json_encode($block->config_json ?? [], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    public function saveConfig(UpdateSiteHomeBlockConfigAction $action): void
    {
        if ($this->editingBlockId === null) {
            throw ValidationException::withMessages(['configJson' => 'Choose a block to edit.']);
        }

        /** @var Site $site */
        $site = $this->getRecord();
        $block = $site->homeBlocks()->findOrFail($this->editingBlockId);
        $action->handle($site, $block, $this->decodedConfig());
        $this->editingBlockId = null;
        $this->configJson = '{}';
        Notification::make()->title('Block configuration saved')->success()->send();
    }

    public function toggle(int $homeBlockId, ToggleSiteHomeBlockAction $action): void
    {
        /** @var Site $site */
        $site = $this->getRecord();
        $action->handle($site, $site->homeBlocks()->findOrFail($homeBlockId));
    }

    public function move(int $homeBlockId, string $direction, ReorderSiteHomeBlocksAction $action): void
    {
        if (! in_array($direction, ['up', 'down'], true)) {
            throw ValidationException::withMessages(['order' => 'Direction must be up or down.']);
        }

        /** @var Site $site */
        $site = $this->getRecord();
        $ids = $site->homeBlocks()->orderBy('position')->pluck('id')->all();
        $index = array_search($homeBlockId, $ids, true);

        if ($index === false) {
            throw ValidationException::withMessages(['order' => 'Unknown site home block.']);
        }

        $target = $direction === 'up' ? $index - 1 : $index + 1;
        if (isset($ids[$target])) {
            [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];
            $action->handle($site, $ids);
        }
    }

    /** @return array<string, mixed> */
    private function decodedConfig(): array
    {
        $decoded = json_decode($this->configJson, true);
        if (! is_array($decoded) || array_is_list($decoded)) {
            throw ValidationException::withMessages(['configJson' => 'Configuration must be a JSON object.']);
        }

        return $decoded;
    }
}
