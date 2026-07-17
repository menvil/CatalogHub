<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SyncLogResource\Pages;
use App\Models\SyncLog;
use App\Models\User;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class SyncLogResource extends Resource
{
    protected static ?string $model = SyncLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Sync';

    protected static ?string $navigationLabel = 'Sync Logs';

    protected static ?string $modelLabel = 'Sync Log';

    protected static ?string $pluralModelLabel = 'Sync Logs';

    protected static ?string $slug = 'sync/logs';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return self::canManageSync();
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof SyncLog && self::canManageSync();
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

    /** @return Builder<SyncLog> */
    public static function getEloquentQuery(): Builder
    {
        return SyncLog::query()
            ->with(['site', 'centralProduct', 'centralCategory', 'triggeredByUser']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('operation')->searchable()->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => self::statusColor($state))
                    ->sortable(),
                TextColumn::make('site.name')->label('Site')->placeholder('All sites')->searchable()->sortable(),
                TextColumn::make('centralProduct.name')->label('Product')->placeholder('None')->searchable()->toggleable(),
                TextColumn::make('centralCategory.name')->label('Category')->placeholder('None')->searchable()->toggleable(),
                TextColumn::make('triggered_by')->label('Trigger')->badge()->sortable(),
                TextColumn::make('affected_count')->label('Affected')->numeric()->sortable(),
                TextColumn::make('started_at')->dateTime()->sortable()->placeholder('Not started'),
                TextColumn::make('finished_at')->dateTime()->sortable()->placeholder('In progress'),
                TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(80)
                    ->color('danger')
                    ->placeholder('None')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'queued' => 'Queued',
                    'running' => 'Running',
                    'completed' => 'Completed',
                    'failed' => 'Failed',
                ]),
                SelectFilter::make('operation')->options(fn (): array => SyncLog::query()
                    ->distinct()
                    ->orderBy('operation')
                    ->pluck('operation', 'operation')
                    ->all()),
                SelectFilter::make('site_id')->label('Site')->relationship('site', 'name'),
                SelectFilter::make('triggered_by')->label('Trigger')->options([
                    'system' => 'System',
                    'user' => 'User',
                    'correction' => 'Correction',
                    'import' => 'Import',
                    'price_update' => 'Price update',
                ]),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(filled($data['from'] ?? null), fn (Builder $query): Builder => $query
                            ->where('created_at', '>=', CarbonImmutable::parse((string) $data['from'])->startOfDay()))
                        ->when(filled($data['until'] ?? null), fn (Builder $query): Builder => $query
                            ->where('created_at', '<', CarbonImmutable::parse((string) $data['until'])->addDay()->startOfDay()))),
            ])
            ->recordActions([ViewAction::make()])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No sync logs')
            ->emptyStateDescription('Sync and correction activity will appear here.');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('operation'),
            TextEntry::make('status')
                ->badge()
                ->color(fn (string $state): string => self::statusColor($state)),
            TextEntry::make('site.name')
                ->label('Site')
                ->placeholder('All sites')
                ->url(fn (SyncLog $record): ?string => $record->site_id === null
                    ? null
                    : SiteResource::getUrl('edit', ['record' => $record->site_id])),
            TextEntry::make('centralProduct.name')
                ->label('Product')
                ->placeholder('None')
                ->url(fn (SyncLog $record): ?string => $record->central_product_id === null
                    ? null
                    : CentralProductResource::getUrl('view', ['record' => $record->central_product_id])),
            TextEntry::make('centralCategory.name')
                ->label('Category')
                ->placeholder('None')
                ->url(fn (SyncLog $record): ?string => $record->central_category_id === null
                    ? null
                    : CentralCategoryResource::getUrl('edit', ['record' => $record->central_category_id])),
            TextEntry::make('triggered_by')->label('Trigger')->badge(),
            TextEntry::make('triggeredByUser.name')->label('Triggered by user')->placeholder('System'),
            TextEntry::make('affected_count')->label('Affected')->numeric(),
            TextEntry::make('started_at')->dateTime()->placeholder('Not started'),
            TextEntry::make('finished_at')->dateTime()->placeholder('In progress'),
            TextEntry::make('duration')
                ->state(fn (SyncLog $record): string => self::duration($record)),
            TextEntry::make('error_message')
                ->label('Error')
                ->color('danger')
                ->placeholder('None')
                ->columnSpanFull(),
            TextEntry::make('context')
                ->state(fn (SyncLog $record): string => self::prettyJson($record->context_json))
                ->copyable()
                ->columnSpanFull(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSyncLogs::route('/'),
            'view' => Pages\ViewSyncLog::route('/{record}'),
        ];
    }

    private static function canManageSync(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasCatalogHubPermission('central.manage');
    }

    private static function statusColor(string $status): string
    {
        return match ($status) {
            'completed' => 'success',
            'failed' => 'danger',
            'running' => 'warning',
            default => 'gray',
        };
    }

    private static function duration(SyncLog $log): string
    {
        if ($log->started_at === null || $log->finished_at === null) {
            return 'In progress';
        }

        return ((int) round($log->started_at->diffInSeconds($log->finished_at))).' seconds';
    }

    private static function prettyJson(mixed $value): string
    {
        return json_encode(
            $value ?? [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ) ?: 'Empty';
    }
}
