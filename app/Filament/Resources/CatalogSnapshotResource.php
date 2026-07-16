<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CatalogSnapshotResource\Pages;
use App\Models\CatalogSnapshot;
use App\Models\User;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
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
use Illuminate\Support\Number;
use UnitEnum;

final class CatalogSnapshotResource extends Resource
{
    protected static ?string $model = CatalogSnapshot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Backup & Export';

    protected static ?string $navigationLabel = 'Export History';

    protected static ?string $pluralModelLabel = 'Export History';

    protected static ?string $modelLabel = 'Catalog Snapshot';

    protected static ?string $slug = 'snapshots';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return self::canManageSnapshots();
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof CatalogSnapshot && self::canManageSnapshots();
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

    /** @return Builder<CatalogSnapshot> */
    public static function getEloquentQuery(): Builder
    {
        return CatalogSnapshot::query()->with('createdBy');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uuid')->copyable()->searchable()->limit(18),
                TextColumn::make('snapshot_type')->label('Type')->badge()->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => self::statusColor($state))
                    ->sortable(),
                TextColumn::make('file_count')
                    ->label('Files')
                    ->state(fn (CatalogSnapshot $record): int => count($record->files_json ?? [])),
                TextColumn::make('total_size')
                    ->label('Size')
                    ->state(fn (CatalogSnapshot $record): int => self::totalSize($record))
                    ->formatStateUsing(fn (int $state): string => Number::fileSize($state, precision: 0)),
                TextColumn::make('createdBy.name')->label('Created by')->placeholder('System')->sortable(),
                TextColumn::make('started_at')->dateTime()->placeholder('Not started')->sortable(),
                TextColumn::make('completed_at')->dateTime()->placeholder('Not completed')->sortable(),
                TextColumn::make('failure_reason')->label('Failure')->limit(60)->color('danger')->placeholder('None')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'generating' => 'Generating',
                    'completed' => 'Completed',
                    'failed' => 'Failed',
                    'expired' => 'Expired',
                ]),
                SelectFilter::make('snapshot_type')->label('Type')->options(['full' => 'Full']),
                SelectFilter::make('created_by_user_id')->label('Created by')->relationship('createdBy', 'name'),
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
            ->recordActions([
                ViewAction::make(),
                Action::make('restoreChecklist')
                    ->label('Restore checklist')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->url(fn (CatalogSnapshot $record): string => self::getUrl('restore-checklist', ['record' => $record])),
                self::downloadAction(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No catalog snapshots')
            ->emptyStateDescription('Generate a portable catalog export to start snapshot history.');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('uuid')->copyable(),
            TextEntry::make('snapshot_type')->label('Type')->badge(),
            TextEntry::make('status')
                ->badge()
                ->color(fn (string $state): string => self::statusColor($state)),
            TextEntry::make('createdBy.name')->label('Created by')->placeholder('System'),
            TextEntry::make('storage_disk')->label('Storage disk'),
            TextEntry::make('storage_path')->label('Storage path')->placeholder('Not generated'),
            TextEntry::make('file_count')
                ->label('File count')
                ->state(fn (CatalogSnapshot $record): int => count($record->files_json ?? [])),
            TextEntry::make('total_size')
                ->label('Total size')
                ->state(fn (CatalogSnapshot $record): string => Number::fileSize(self::totalSize($record), precision: 0)),
            TextEntry::make('started_at')->dateTime()->placeholder('Not started'),
            TextEntry::make('completed_at')->dateTime()->placeholder('Not completed'),
            TextEntry::make('failed_at')->dateTime()->placeholder('Not failed'),
            TextEntry::make('failure_reason')->label('Failure reason')->color('danger')->placeholder('None')->columnSpanFull(),
            TextEntry::make('files')
                ->state(fn (CatalogSnapshot $record): string => self::prettyJson($record->files_json))
                ->copyable()
                ->columnSpanFull(),
            TextEntry::make('metadata')
                ->state(fn (CatalogSnapshot $record): string => self::prettyJson($record->metadata_json))
                ->copyable()
                ->columnSpanFull(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCatalogSnapshots::route('/'),
            'view' => Pages\ViewCatalogSnapshot::route('/{record}'),
            'restore-checklist' => Pages\RestoreChecklistPage::route('/{record}/restore-checklist'),
        ];
    }

    public static function downloadAction(): Action
    {
        return Action::make('downloadFile')
            ->label('Download file')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->visible(fn (CatalogSnapshot $record): bool => $record->isCompleted() && ($record->files_json ?? []) !== [])
            ->schema([
                Select::make('file_key')
                    ->label('Snapshot file')
                    ->options(fn (CatalogSnapshot $record): array => collect($record->files_json ?? [])
                        ->mapWithKeys(fn (array $file, string $key): array => [
                            $key => $key.' — '.($file['path'] ?? 'missing path'),
                        ])
                        ->all())
                    ->required(),
            ])
            ->action(fn (array $data, CatalogSnapshot $record) => redirect()->route(
                'central.snapshots.download',
                [$record, (string) $data['file_key']],
            ));
    }

    private static function canManageSnapshots(): bool
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
            'generating' => 'warning',
            'expired' => 'gray',
            default => 'info',
        };
    }

    private static function totalSize(CatalogSnapshot $snapshot): int
    {
        return (int) collect($snapshot->files_json ?? [])->sum(
            fn (array $file): int => (int) ($file['file_size'] ?? 0),
        );
    }

    private static function prettyJson(mixed $value): string
    {
        return json_encode(
            $value ?? [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ) ?: 'Empty';
    }
}
