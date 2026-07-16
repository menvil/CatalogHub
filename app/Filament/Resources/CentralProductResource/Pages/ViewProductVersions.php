<?php

namespace App\Filament\Resources\CentralProductResource\Pages;

use App\Filament\Resources\CentralProductResource;
use App\Models\ProductVersion;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class ViewProductVersions extends ManageRelatedRecords
{
    protected static string $resource = CentralProductResource::class;

    protected static string $relationship = 'versions';

    protected static ?string $title = 'Version History';

    public static function canAccess(array $parameters = []): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasCatalogHubPermission('central.view');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('version')->label('Version')->numeric()->sortable(),
                TextColumn::make('change_type')->label('Change type')->badge()->sortable(),
                TextColumn::make('changedBy.name')->label('Changed by')->placeholder('System')->sortable(),
                TextColumn::make('reason')->limit(80)->placeholder('No reason')->wrap(),
                TextColumn::make('diff_json')
                    ->label('Diff')
                    ->state(fn (ProductVersion $record): string => $this->diffSummary($record->diff_json))
                    ->placeholder('No diff'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                Action::make('viewDiff')
                    ->label('View change')
                    ->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->modalHeading(fn (ProductVersion $record): string => "Version {$record->version}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (ProductVersion $record) => view(
                        'filament.resources.central-product-resource.pages.version-history-entry',
                        ['version' => $record],
                    )),
            ])
            ->defaultSort('version', 'desc')
            ->emptyStateHeading('No product versions yet')
            ->emptyStateDescription('Canonical changes will appear here after the product version is incremented.');
    }

    private function diffSummary(mixed $state): string
    {
        if (! is_array($state) || $state === []) {
            return 'No diff';
        }

        return collect(array_keys($state))
            ->map(fn (mixed $field): string => (string) $field)
            ->join(', ');
    }
}
