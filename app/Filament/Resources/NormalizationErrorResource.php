<?php

namespace App\Filament\Resources;

use App\Actions\Imports\ResolveNormalizationErrorAction;
use App\Filament\Resources\NormalizationErrorResource\Pages;
use App\Models\Imports\NormalizationError;
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

final class NormalizationErrorResource extends Resource
{
    protected static ?string $model = NormalizationError::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'Imports';

    protected static ?string $navigationLabel = 'Normalization errors';

    public static function canViewAny(): bool
    {
        return self::canManageImports();
    }

    public static function canView(Model $record): bool
    {
        return self::canManageImports();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $batchId = request()->integer('batch');

        return $batchId > 0 ? $query->where('import_batch_id', $batchId) : $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('severity')->badge()->sortable(),
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('message')->limit(80)->searchable(),
                TextColumn::make('raw_key')->label('Raw key')->placeholder('None'),
                TextColumn::make('batch.id')->label('Batch'),
                TextColumn::make('raw_product_id')->label('Raw product')->placeholder('None'),
                TextColumn::make('normalized_product_draft_id')->label('Draft')->placeholder('None'),
                TextColumn::make('resolved_at')->dateTime()->placeholder('Unresolved')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('batch')->relationship('batch', 'id')->searchable()->preload(),
                SelectFilter::make('severity')->options([
                    'info' => 'Info',
                    'warning' => 'Warning',
                    'error' => 'Error',
                    'critical' => 'Critical',
                ]),
                SelectFilter::make('code')->options(fn (): array => NormalizationError::query()
                    ->distinct()
                    ->orderBy('code')
                    ->pluck('code', 'code')
                    ->all()),
                SelectFilter::make('resolution')->options([
                    'unresolved' => 'Unresolved',
                    'resolved' => 'Resolved',
                ])->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                    'unresolved' => $query->whereNull('resolved_at'),
                    'resolved' => $query->whereNotNull('resolved_at'),
                    default => $query,
                }),
            ])
            ->recordActions([
                ViewAction::make(),
                self::resolveAction(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('severity')->badge(),
            TextEntry::make('code'),
            TextEntry::make('message')->columnSpanFull(),
            TextEntry::make('raw_key')->label('Raw key')->placeholder('None'),
            TextEntry::make('raw_value')->label('Raw value')->placeholder('None'),
            TextEntry::make('batch.id')->label('Import batch'),
            TextEntry::make('raw_product_id')->label('Raw product')->placeholder('None'),
            TextEntry::make('normalized_product_draft_id')->label('Normalized draft')->placeholder('None'),
            TextEntry::make('context')
                ->label('Context')
                ->state(fn (NormalizationError $record): string => (string) json_encode(
                    $record->context_json ?? [],
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
                ))
                ->columnSpanFull(),
            TextEntry::make('resolvedBy.name')->label('Resolved by')->placeholder('Unresolved'),
            TextEntry::make('resolved_at')->dateTime()->placeholder('Unresolved'),
        ]);
    }

    public static function resolveAction(): Action
    {
        return Action::make('resolve')
            ->label('Mark resolved')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (NormalizationError $record): bool => $record->resolved_at === null)
            ->action(fn (NormalizationError $record): NormalizationError => app(ResolveNormalizationErrorAction::class)
                ->handle($record, auth()->user()));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNormalizationErrors::route('/'),
            'view' => Pages\ViewNormalizationError::route('/{record}'),
        ];
    }

    private static function canManageImports(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || $user->isCentralAdmin() || $user->isCatalogEditor());
    }
}
