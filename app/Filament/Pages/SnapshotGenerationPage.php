<?php

namespace App\Filament\Pages;

use App\Models\CatalogSnapshot;
use App\Models\User;
use App\Services\Export\SnapshotGenerationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class SnapshotGenerationPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = 'Backup & Export';

    protected static ?string $navigationLabel = 'Create Snapshot';

    protected static ?string $title = 'Create Snapshot';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.snapshot-generation';

    public ?int $generatedSnapshotId = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasCatalogHubPermission('central.manage');
    }

    public function generatedSnapshot(): ?CatalogSnapshot
    {
        return $this->generatedSnapshotId === null
            ? null
            : CatalogSnapshot::query()->find($this->generatedSnapshotId);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateSnapshot')
                ->label('Generate snapshot')
                ->icon(Heroicon::OutlinedPlay)
                ->color('primary')
                ->schema([
                    Select::make('snapshot_type')
                        ->label('Snapshot type')
                        ->options(['full' => 'Full'])
                        ->default('full')
                        ->required(),
                    CheckboxList::make('included_sections')
                        ->label('Included sections')
                        ->options(self::sectionOptions())
                        ->default(SnapshotGenerationService::sectionKeys())
                        ->columns(2)
                        ->required(),
                ])
                ->requiresConfirmation()
                ->modalDescription('A catalog snapshot is a portable export, not a full database backup.')
                ->action(function (array $data): void {
                    $user = auth()->user();

                    if (! $user instanceof User) {
                        return;
                    }

                    $snapshot = app(SnapshotGenerationService::class)->generate(
                        $user,
                        array_values($data['included_sections']),
                        (string) $data['snapshot_type'],
                    );
                    $this->generatedSnapshotId = $snapshot->getKey();

                    Notification::make()
                        ->title('Snapshot generated')
                        ->body("Snapshot {$snapshot->uuid} completed.")
                        ->success()
                        ->send();
                }),
        ];
    }

    /** @return array<string, string> */
    private static function sectionOptions(): array
    {
        return [
            'products' => 'Products',
            'categories' => 'Categories',
            'brands' => 'Brands',
            'attributes' => 'Attribute schema',
            'attribute_values' => 'Attribute values',
            'translations' => 'Translations',
            'media_manifest' => 'Media manifest',
            'site_config' => 'Site configuration',
        ];
    }
}
