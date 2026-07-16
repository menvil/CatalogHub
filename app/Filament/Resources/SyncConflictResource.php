<?php

namespace App\Filament\Resources;

use App\Actions\Sync\KeepLocalOverrideAction;
use App\Actions\Sync\UseCentralValueAction;
use App\Filament\Resources\SyncConflictResource\Pages;
use App\Models\SyncConflict;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class SyncConflictResource extends Resource
{
    protected static ?string $model = SyncConflict::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = 'Sync';

    protected static ?string $navigationLabel = 'Sync Conflicts';

    protected static ?string $modelLabel = 'Sync Conflict';

    protected static ?string $pluralModelLabel = 'Sync Conflicts';

    protected static ?string $slug = 'sync/conflicts';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return self::canResolve();
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof SyncConflict && self::canResolve();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /** @return Builder<SyncConflict> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->open()
            ->with(['site', 'centralProduct']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('site.name')->label('Site')->searchable()->sortable(),
                TextColumn::make('centralProduct.name')->label('Product')->placeholder('Other entity')->searchable()->sortable(),
                TextColumn::make('entity_type')->label('Entity')->badge()->sortable(),
                TextColumn::make('field_path')->label('Field')->searchable()->sortable(),
                TextColumn::make('conflict_type')->label('Conflict type')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('site_id')->label('Site')->relationship('site', 'name'),
                SelectFilter::make('conflict_type')->options(fn (): array => SyncConflict::query()
                    ->distinct()
                    ->orderBy('conflict_type')
                    ->pluck('conflict_type', 'conflict_type')
                    ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                ...self::resolutionActions(),
            ])
            ->defaultSort('created_at')
            ->emptyStateHeading('No open sync conflicts')
            ->emptyStateDescription('All central and local differences have been reviewed.');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('site.name')->label('Site'),
            TextEntry::make('centralProduct.name')->label('Product')->placeholder('Other entity'),
            TextEntry::make('entity_type')->label('Entity type')->badge(),
            TextEntry::make('field_path')->label('Field path'),
            TextEntry::make('conflict_type')->label('Conflict type')->badge(),
            TextEntry::make('status')->badge(),
            TextEntry::make('central_value')
                ->label('Central value')
                ->state(fn (SyncConflict $record): string => self::prettyJson($record->central_value_json))
                ->copyable()
                ->columnSpanFull(),
            TextEntry::make('local_value')
                ->label('Local value')
                ->state(fn (SyncConflict $record): string => self::prettyJson($record->local_value_json))
                ->copyable()
                ->columnSpanFull(),
            TextEntry::make('metadata')
                ->state(fn (SyncConflict $record): string => self::prettyJson($record->metadata_json))
                ->copyable()
                ->columnSpanFull(),
            TextEntry::make('created_at')->dateTime(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSyncConflicts::route('/'),
            'view' => Pages\ViewSyncConflict::route('/{record}'),
        ];
    }

    /** @return list<Action> */
    public static function resolutionActions(): array
    {
        return [
            Action::make('useCentralValue')
                ->label('Use central value')
                ->icon(Heroicon::OutlinedArrowDown)
                ->color('success')
                ->requiresConfirmation()
                ->action(function (SyncConflict $record): SyncConflict {
                    $user = auth()->user();

                    return $user instanceof User
                        ? app(UseCentralValueAction::class)->handle($user, $record)
                        : $record;
                }),
            Action::make('keepLocalOverride')
                ->label('Keep local override')
                ->icon(Heroicon::OutlinedBookmark)
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (SyncConflict $record): SyncConflict {
                    $user = auth()->user();

                    return $user instanceof User
                        ? app(KeepLocalOverrideAction::class)->handle($user, $record)
                        : $record;
                }),
            Action::make('convertToMarketOverride')->label('Convert to market override')->icon(Heroicon::OutlinedGlobeAlt)->disabled(),
        ];
    }

    private static function canResolve(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasCatalogHubPermission('central.manage');
    }

    private static function prettyJson(mixed $value): string
    {
        return json_encode(
            $value ?? [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ) ?: 'Empty';
    }
}
