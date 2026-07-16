<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MissingMediaReportResource\Pages;
use App\Models\MediaManifest;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class MissingMediaReportResource extends Resource
{
    protected static ?string $model = MediaManifest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'Backup & Export';

    protected static ?string $navigationLabel = 'Missing Media';

    protected static ?string $pluralModelLabel = 'Missing Media';

    protected static ?string $modelLabel = 'Missing Media Record';

    protected static ?string $slug = 'media/missing-report';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasCatalogHubPermission('central.manage');
    }

    public static function canView(Model $record): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /** @return Builder<MediaManifest> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNull('catalog_snapshot_id')
            ->where('status', 'missing')
            ->with('mediaAsset');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('media_asset_id')->label('Asset ID')->sortable(),
                TextColumn::make('asset_uuid')->label('Asset UUID')->copyable()->limit(16),
                TextColumn::make('original_path')->label('Original path')->searchable()->wrap(),
                TextColumn::make('problem_type')
                    ->label('Missing type')
                    ->badge()
                    ->state(fn (MediaManifest $record): string => self::problemType($record)),
                TextColumn::make('missing_paths')
                    ->label('Missing paths')
                    ->state(fn (MediaManifest $record): string => implode("\n", $record->metadata_json['missing_paths'] ?? []))
                    ->wrap(),
                TextColumn::make('status')->badge()->color('danger'),
                TextColumn::make('last_checked_at')
                    ->label('Last checked')
                    ->state(fn (MediaManifest $record): string => (string) ($record->metadata_json['last_checked_at'] ?? 'Unknown')),
            ])
            ->filters([
                SelectFilter::make('status')->options(['missing' => 'Missing']),
                SelectFilter::make('problem_type')
                    ->label('Missing type')
                    ->options([
                        'original' => 'Original',
                        'variant' => 'Variant',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'original' => $query->whereJsonContains('metadata_json->missing_original', true),
                        'variant' => $query->where('metadata_json->missing_variant_count', '>', 0),
                        default => $query,
                    }),
            ])
            ->recordActions([
                Action::make('viewAsset')
                    ->label('View media asset')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (MediaManifest $record): ?string => $record->media_asset_id === null
                        ? null
                        : route('central.media.show', ['asset' => $record->media_asset_id])),
            ])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('No missing media')
            ->emptyStateDescription('Run the media integrity check to refresh this report.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMissingMedia::route('/'),
        ];
    }

    private static function problemType(MediaManifest $record): string
    {
        $metadata = $record->metadata_json ?? [];

        return match (true) {
            (bool) ($metadata['missing_original'] ?? false) && (int) ($metadata['missing_variant_count'] ?? 0) > 0 => 'Original + variant',
            (bool) ($metadata['missing_original'] ?? false) => 'Original',
            default => 'Variant',
        };
    }
}
