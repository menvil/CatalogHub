<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RawProductResource\Pages;
use App\Models\Imports\RawProduct;
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

final class RawProductResource extends Resource
{
    protected static ?string $model = RawProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCodeBracket;

    protected static string|UnitEnum|null $navigationGroup = 'Imports';

    protected static ?string $navigationLabel = 'Raw products';

    protected static ?string $recordTitleAttribute = 'raw_title';

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
                TextColumn::make('raw_title')->label('Title')->searchable()->placeholder('Untitled'),
                TextColumn::make('raw_brand')->label('Brand')->searchable()->placeholder('Unknown'),
                TextColumn::make('raw_category')->label('Category')->searchable()->placeholder('Unknown'),
                TextColumn::make('source.name')->label('Source'),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('source_row_number')->label('Row')->numeric(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ViewAction::make()]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('raw_title')->label('Raw title')->placeholder('Missing'),
            TextEntry::make('raw_brand')->label('Raw brand')->placeholder('Missing'),
            TextEntry::make('raw_category')->label('Raw category')->placeholder('Missing'),
            TextEntry::make('status')->badge(),
            TextEntry::make('error_message')
                ->label('Import error')
                ->placeholder('None')
                ->columnSpanFull(),
            TextEntry::make('source.name')->label('Source'),
            TextEntry::make('batch.id')->label('Import batch'),
            TextEntry::make('external_id')->label('External ID')->placeholder('Missing'),
            TextEntry::make('source_row_number')->label('Source row')->placeholder('Missing'),
            TextEntry::make('payload_hash')->label('Payload hash')->copyable()->columnSpanFull(),
            TextEntry::make('raw_payload_json')
                ->label('Raw payload JSON')
                ->formatStateUsing(fn (mixed $state): string => json_encode(
                    $state ?? [],
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                ))
                ->copyable()
                ->columnSpanFull(),
            TextEntry::make('normalization_errors')
                ->label('Related errors')
                ->state(fn (RawProduct $record): string => $record->errors()
                    ->orderBy('created_at')
                    ->get()
                    ->map(fn ($error): string => "{$error->code}: {$error->message}")
                    ->implode("\n"))
                ->placeholder('None')
                ->columnSpanFull(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRawProducts::route('/'),
            'view' => Pages\ViewRawProduct::route('/{record}'),
        ];
    }

    private static function canManageImports(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canManageImports();
    }
}
