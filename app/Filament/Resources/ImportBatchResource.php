<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImportBatchResource\Pages;
use App\Models\Imports\DuplicateCandidate;
use App\Models\Imports\ImportBatch;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class ImportBatchResource extends Resource
{
    protected static ?string $model = ImportBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Imports';

    protected static ?string $navigationLabel = 'Import batches';

    protected static ?string $recordTitleAttribute = 'id';

    public static function canViewAny(): bool
    {
        return self::canManageImports();
    }

    public static function canView(Model $record): bool
    {
        return self::canManageImports();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Batch')->sortable(),
                TextColumn::make('source.name')->label('Source')->searchable()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('original_filename')->placeholder('No filename'),
                TextColumn::make('total_items')->numeric()->sortable(),
                TextColumn::make('raw_items_count')->label('Raw')->numeric(),
                TextColumn::make('drafts_count')->label('Drafts')->numeric(),
                TextColumn::make('failed_count')->label('Failed')->numeric(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ViewAction::make()]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('source.name')->label('Source'),
            TextEntry::make('status')->badge(),
            TextEntry::make('original_filename')->placeholder('No filename'),
            TextEntry::make('started_at')->dateTime()->placeholder('Not started'),
            TextEntry::make('finished_at')->dateTime()->placeholder('Not finished'),
            TextEntry::make('total_items')->numeric(),
            TextEntry::make('raw_items_count')->label('Raw products')->numeric(),
            TextEntry::make('drafts_count')->label('Drafts')->numeric(),
            TextEntry::make('approved_count')->label('Approved')->numeric(),
            TextEntry::make('rejected_count')->label('Rejected')->numeric(),
            TextEntry::make('failed_count')->label('Failed')->numeric(),
            TextEntry::make('artifacts_count')
                ->label('Artifacts')
                ->state(fn (ImportBatch $record): int => $record->artifacts()->count()),
            TextEntry::make('errors_count')
                ->label('Normalization errors')
                ->state(fn (ImportBatch $record): int => $record->errors()->count()),
            TextEntry::make('duplicates_count')
                ->label('Duplicate candidates')
                ->state(fn (ImportBatch $record): int => DuplicateCandidate::query()
                    ->where('import_batch_id', $record->id)
                    ->count()),
            TextEntry::make('error_message')
                ->label('Batch error')
                ->placeholder('None')
                ->columnSpanFull(),
            TextEntry::make('metadata_json')
                ->label('Metadata')
                ->formatStateUsing(fn (mixed $state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                ->columnSpanFull(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImportBatches::route('/'),
            'view' => Pages\ViewImportBatch::route('/{record}'),
        ];
    }

    private static function canManageImports(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->can('central.view');
    }
}
