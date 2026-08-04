<?php

namespace App\Filament\Resources;

use App\Enums\PriceSourceSyncStatus;
use App\Filament\Resources\PriceSourceSyncLogResource\Pages;
use App\Models\PriceSourceSyncLog;
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

final class PriceSourceSyncLogResource extends Resource
{
    protected static ?string $model = PriceSourceSyncLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Pricing';

    protected static ?string $navigationLabel = 'Price sync logs';

    public static function canViewAny(): bool
    {
        return self::canManagePrices();
    }

    public static function canView(Model $record): bool
    {
        return self::canManagePrices();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('priceSource.name')->label('Source')->searchable()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('started_at')->dateTime()->sortable()->placeholder('Not started'),
                TextColumn::make('finished_at')->dateTime()->sortable()->placeholder('Not finished'),
                TextColumn::make('items_fetched')->label('Fetched')->numeric(),
                TextColumn::make('items_normalized')->label('Normalized')->numeric(),
                TextColumn::make('items_matched')->label('Matched')->numeric(),
                TextColumn::make('items_updated')->label('Updated')->numeric(),
                TextColumn::make('error_message')->label('Error')->limit(80)->placeholder('None'),
            ])
            ->filters([
                SelectFilter::make('price_source_id')
                    ->label('Source')
                    ->relationship('priceSource', 'name'),
                SelectFilter::make('status')->options(PriceSourceSyncStatus::options()),
                Filter::make('started_at')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(filled($data['from'] ?? null), fn (Builder $query): Builder => $query
                            ->where('started_at', '>=', CarbonImmutable::parse((string) $data['from'])->startOfDay()))
                        ->when(filled($data['until'] ?? null), fn (Builder $query): Builder => $query
                            ->where('started_at', '<', CarbonImmutable::parse((string) $data['until'])->addDay()->startOfDay()))),
            ])
            ->recordActions([ViewAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('priceSource.name')->label('Source'),
            TextEntry::make('status')->badge(),
            TextEntry::make('started_at')->dateTime()->placeholder('Not started'),
            TextEntry::make('finished_at')->dateTime()->placeholder('Not finished'),
            TextEntry::make('items_fetched')->label('Fetched')->numeric(),
            TextEntry::make('items_normalized')->label('Normalized')->numeric(),
            TextEntry::make('items_matched')->label('Matched')->numeric(),
            TextEntry::make('items_updated')->label('Updated')->numeric(),
            TextEntry::make('error_message')->label('Error')->placeholder('None')->columnSpanFull(),
            TextEntry::make('metadata')
                ->formatStateUsing(fn (mixed $state): string => (string) json_encode(
                    $state ?? [],
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                ))
                ->copyable()
                ->columnSpanFull(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPriceSourceSyncLogs::route('/'),
            'view' => Pages\ViewPriceSourceSyncLog::route('/{record}'),
        ];
    }

    private static function canManagePrices(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->can('prices.manage');
    }
}
