<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NormalizedProductDraftResource\Pages;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class NormalizedProductDraftResource extends Resource
{
    protected static ?string $model = NormalizedProductDraft::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Imports';

    protected static ?string $navigationLabel = 'Normalized drafts';

    protected static ?string $recordTitleAttribute = 'title';

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
                TextColumn::make('id')->sortable(),
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('brand.name')->label('Brand')->placeholder('Unresolved'),
                TextColumn::make('category.name')->label('Category')->placeholder('Unresolved'),
                TextColumn::make('confidence')->numeric(decimalPlaces: 4)->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('duplicate_candidates_count')
                    ->counts('duplicateCandidates')
                    ->label('Duplicates'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ViewAction::make()]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('title')->label('Normalized title'),
            TextEntry::make('status')->badge(),
            TextEntry::make('brand.name')->label('Brand')->placeholder('Unresolved'),
            TextEntry::make('category.name')->label('Category')->placeholder('Unresolved'),
            TextEntry::make('confidence')->numeric(decimalPlaces: 4),
            TextEntry::make('matchedCentralProduct.name')->label('Matched central product')->placeholder('None'),
            TextEntry::make('rawProduct.raw_title')->label('Raw title')->placeholder('Missing'),
            TextEntry::make('raw_payload')
                ->label('Raw payload')
                ->state(fn (NormalizedProductDraft $record): string => self::prettyJson($record->rawProduct->raw_payload_json))
                ->copyable()
                ->columnSpanFull(),
            TextEntry::make('normalized_payload_json')
                ->label('Normalized payload')
                ->formatStateUsing(fn (mixed $state): string => self::prettyJson($state))
                ->copyable()
                ->columnSpanFull(),
            TextEntry::make('attributes_json')
                ->label('Normalized attributes')
                ->formatStateUsing(fn (mixed $state): string => self::prettyJson($state))
                ->copyable()
                ->columnSpanFull(),
            TextEntry::make('media_json')
                ->label('Media candidates')
                ->formatStateUsing(fn (mixed $state): string => self::prettyJson($state))
                ->columnSpanFull(),
            TextEntry::make('duplicate_summary')
                ->label('Duplicate candidates')
                ->state(function (NormalizedProductDraft $record): ?string {
                    $candidates = $record->duplicateCandidates()
                        ->get(['candidate_type', 'candidate_id', 'score', 'reason_json', 'status'])
                        ->toArray();

                    return $candidates === [] ? null : self::prettyJson($candidates);
                })
                ->placeholder('None')
                ->columnSpanFull(),
            TextEntry::make('error_summary')
                ->label('Normalization errors')
                ->state(function (NormalizedProductDraft $record): ?string {
                    $errors = $record->errors()
                        ->get(['severity', 'code', 'message', 'raw_key', 'raw_value', 'resolved_at'])
                        ->toArray();

                    return $errors === [] ? null : self::prettyJson($errors);
                })
                ->placeholder('None')
                ->columnSpanFull(),
            TextEntry::make('review_notes')->placeholder('None')->columnSpanFull(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNormalizedProductDrafts::route('/'),
            'view' => Pages\ViewNormalizedProductDraft::route('/{record}'),
        ];
    }

    private static function prettyJson(mixed $value): string
    {
        return json_encode(
            $value ?? [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    private static function canManageImports(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || $user->isCentralAdmin() || $user->isCatalogEditor());
    }
}
