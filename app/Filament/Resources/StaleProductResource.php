<?php

namespace App\Filament\Resources;

use App\Actions\Sync\RebuildSiteProductProjectionAction;
use App\Filament\Resources\StaleProductResource\Pages;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\SiteProduct;
use App\Models\User;
use App\Queries\Sync\StaleProductVersionGapQuery;
use App\Services\Sync\StaleProductDetector;
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

final class StaleProductResource extends Resource
{
    protected static ?string $model = SiteProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Sync';

    protected static ?string $navigationLabel = 'Stale Products';

    protected static ?string $modelLabel = 'Stale Product';

    protected static ?string $pluralModelLabel = 'Stale Products';

    protected static ?string $slug = 'sync/stale-products';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return self::canManageSync();
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof SiteProduct && self::canManageSync();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /** @return Builder<SiteProduct> */
    public static function getEloquentQuery(): Builder
    {
        return app(StaleProductDetector::class)
            ->staleAcrossSites()
            ->with(['site', 'centralProduct.category']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('site.name')->label('Site')->searchable()->sortable(),
                TextColumn::make('centralProduct.name')->label('Product')->searchable()->sortable(),
                TextColumn::make('centralProduct.category.name')->label('Category')->placeholder('None')->sortable(),
                TextColumn::make('centralProduct.version')->label('Central version')->numeric()->sortable(),
                TextColumn::make('published_version')->label('Published version')->numeric()->sortable(),
                TextColumn::make('version_gap')
                    ->label('Version gap')
                    ->state(fn (SiteProduct $record): int => max(0, $record->centralProduct->version - $record->published_version))
                    ->badge(),
                TextColumn::make('last_synced_at')->dateTime()->placeholder('Never')->sortable(),
                TextColumn::make('sync_status')->badge()->placeholder('Pending')->sortable(),
            ])
            ->filters([
                SelectFilter::make('site_id')->label('Site')->relationship('site', 'name'),
                SelectFilter::make('central_category_id')
                    ->label('Category')
                    ->options(fn (): array => CentralCategory::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(function (Builder $query, array $data): Builder {
                        $categoryId = $data['value'] ?? null;

                        return $query->when(
                            filled($categoryId),
                            fn (Builder $query): Builder => $query->whereHas(
                                'centralProduct',
                                fn (Builder $query): Builder => $query->where('central_category_id', $categoryId),
                            ),
                        );
                    }),
                SelectFilter::make('sync_status')->label('Sync status')->options([
                    'failed' => 'Failed',
                    'running' => 'Running',
                    'completed' => 'Completed',
                ]),
                SelectFilter::make('version_gap')
                    ->label('Minimum version gap')
                    ->options([1 => '1+', 2 => '2+', 5 => '5+'])
                    ->query(function (Builder $query, array $data): Builder {
                        $gap = $data['value'] ?? null;

                        return $query->when(
                            filled($gap),
                            fn (Builder $query): Builder => app(StaleProductVersionGapQuery::class)
                                ->apply($query, (int) $gap),
                        );
                    }),
            ])
            ->recordActions([
                Action::make('rebuild')
                    ->label('Rebuild projection')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->requiresConfirmation()
                    ->action(function (SiteProduct $record): SiteProduct {
                        $user = auth()->user();

                        return $user instanceof User
                            ? app(RebuildSiteProductProjectionAction::class)->handle($user, $record)
                            : $record;
                    }),
                Action::make('viewProduct')
                    ->label('View product')
                    ->icon(Heroicon::OutlinedRectangleStack)
                    ->url(fn (SiteProduct $record): string => CentralProductResource::getUrl(
                        'view',
                        ['record' => $record->central_product_id],
                    )),
                Action::make('viewProjections')
                    ->label('View projections')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (): string => SiteProductProjectionResource::getUrl()),
            ])
            ->defaultSort('last_synced_at')
            ->emptyStateHeading('All site products are current')
            ->emptyStateDescription('No site product is behind its central product version.');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListStaleProducts::route('/')];
    }

    private static function canManageSync(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasCatalogHubPermission('central.manage');
    }
}
