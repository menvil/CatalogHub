<?php

namespace App\Filament\Resources;

use App\Actions\Imports\ReviewDuplicateCandidateAction;
use App\Filament\Resources\DuplicateCandidateResource\Pages;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Imports\DuplicateCandidate;
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

final class DuplicateCandidateResource extends Resource
{
    protected static ?string $model = DuplicateCandidate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquare2Stack;

    protected static string|UnitEnum|null $navigationGroup = 'Imports';

    protected static ?string $navigationLabel = 'Duplicate candidates';

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
                TextColumn::make('draft.title')->label('Draft')->searchable(),
                TextColumn::make('candidate_name')
                    ->label('Candidate')
                    ->state(fn (DuplicateCandidate $record): string => self::candidateName($record)),
                TextColumn::make('candidate_type')->label('Type')->badge(),
                TextColumn::make('score')->numeric(decimalPlaces: 4)->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('reviewedBy.name')->label('Reviewed by')->placeholder('Pending'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'confirmed_duplicate' => 'Duplicate',
                    'not_duplicate' => 'Not duplicate',
                ]),
                SelectFilter::make('candidate_type')->options([
                    'central_product' => 'Central product',
                    'normalized_product_draft' => 'Draft',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                self::reviewAction('markDuplicate', 'Mark duplicate', 'confirmed_duplicate', 'warning'),
                self::reviewAction('markNotDuplicate', 'Not duplicate', 'not_duplicate', 'gray'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('draft.title')->label('Draft'),
            TextEntry::make('candidate_name')
                ->label('Candidate')
                ->state(fn (DuplicateCandidate $record): string => self::candidateName($record)),
            TextEntry::make('candidate_type')->label('Candidate type')->badge(),
            TextEntry::make('candidate_id')->label('Candidate ID'),
            TextEntry::make('score')->numeric(decimalPlaces: 4),
            TextEntry::make('status')->badge(),
            TextEntry::make('reason_json')
                ->label('Match reasons')
                ->state(fn (DuplicateCandidate $record): string => (string) json_encode(
                    $record->reason_json ?? [],
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
                ))
                ->columnSpanFull(),
            TextEntry::make('reviewedBy.name')->label('Reviewed by')->placeholder('Pending'),
            TextEntry::make('reviewed_at')->dateTime()->placeholder('Pending'),
        ]);
    }

    public static function reviewAction(
        string $name,
        string $label,
        string $decision,
        string $color,
    ): Action {
        return Action::make($name)
            ->label($label)
            ->color($color)
            ->requiresConfirmation()
            ->action(fn (DuplicateCandidate $record): DuplicateCandidate => app(ReviewDuplicateCandidateAction::class)
                ->handle($record, auth()->user(), $decision));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDuplicateCandidates::route('/'),
            'view' => Pages\ViewDuplicateCandidate::route('/{record}'),
        ];
    }

    private static function candidateName(DuplicateCandidate $candidate): string
    {
        if ($candidate->candidate_type === 'central_product') {
            return CentralProduct::query()->find($candidate->candidate_id)?->name ?? "Central product #{$candidate->candidate_id}";
        }

        return "Draft #{$candidate->candidate_id}";
    }

    private static function canManageImports(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || $user->isCentralAdmin() || $user->isCatalogEditor());
    }
}
