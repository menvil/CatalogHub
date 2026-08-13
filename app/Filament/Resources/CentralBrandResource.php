<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\CentralBrandStatus;
use App\Filament\Resources\CentralBrandResource\Pages;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class CentralBrandResource extends Resource
{
    protected static ?string $model = CentralBrand::class;

    protected static ?string $slug = 'brands';

    protected static ?string $modelLabel = 'Brand';

    protected static ?string $pluralModelLabel = 'Brands';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Brands';

    protected static string|UnitEnum|null $navigationGroup = 'Central Catalog';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewAny(): bool
    {
        return self::canManageBrands();
    }

    public static function canView(Model $record): bool
    {
        return self::canManageBrands();
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

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }

    public static function canRestore(Model $record): bool
    {
        return false;
    }

    public static function canRestoreAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extraAttributes([
                'data-screen-id' => 'CA-011',
                'data-screen-region' => 'brands-table',
            ])
            ->columns([
                TextColumn::make('name')
                    ->weight('semibold')
                    ->searchable()
                    ->sortable(['normalized_name']),
                TextColumn::make('slug')
                    ->fontFamily(FontFamily::Mono)
                    ->color('gray')
                    ->searchable()
                    ->sortable()
                    ->visibleFrom('sm'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(static fn (CentralBrandStatus|string|null $state): string => self::statusLabel($state))
                    ->color(static fn (CentralBrandStatus|string|null $state): string => CentralBrandStatus::colorFor($state))
                    ->sortable(),
                TextColumn::make('country_code')
                    ->label('Country')
                    ->placeholder('—')
                    ->visibleFrom('md'),
                TextColumn::make('website_url')
                    ->label('Website')
                    ->formatStateUsing(static fn (?string $state): string => self::websiteLabel($state))
                    ->url(static fn (CentralBrand $record): ?string => $record->website_url)
                    ->openUrlInNewTab()
                    ->limit(36)
                    ->placeholder('—')
                    ->visibleFrom('lg'),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->date('M j, Y')
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(CentralBrandStatus::options()),
            ])
            ->searchPlaceholder('Search brands…')
            ->defaultSort('name')
            ->defaultPaginationPageOption(20)
            ->paginationPageOptions([20, 50, 100])
            ->recordActions([])
            ->toolbarActions([])
            ->emptyStateHeading(static fn (Pages\ListCentralBrands $livewire): string => $livewire->hasActiveBrandTableConstraints()
                ? 'No matching brands'
                : 'No brands yet')
            ->emptyStateDescription(static fn (Pages\ListCentralBrands $livewire): string => $livewire->hasActiveBrandTableConstraints()
                ? 'No brands match your current search or filters.'
                : 'Canonical brands will appear here once they are created.')
            ->emptyStateActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCentralBrands::route('/'),
        ];
    }

    private static function canManageBrands(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->can('catalog.products.manage');
    }

    private static function statusLabel(CentralBrandStatus|string|null $status): string
    {
        if (! $status instanceof CentralBrandStatus) {
            $status = CentralBrandStatus::tryFrom((string) $status);
        }

        return $status?->label() ?? '—';
    }

    private static function websiteLabel(?string $websiteUrl): string
    {
        if ($websiteUrl === null) {
            return '—';
        }

        $host = parse_url($websiteUrl, PHP_URL_HOST);

        return is_string($host) ? preg_replace('/\Awww\./i', '', $host) ?? $host : $websiteUrl;
    }
}
