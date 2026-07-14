<?php

namespace App\Filament\Resources;

use App\Actions\Reviews\ApproveReviewAction;
use App\Actions\Reviews\RejectReviewAction;
use App\Enums\ReviewStatus;
use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Site';

    protected static ?string $navigationLabel = 'Reviews';

    public static function canViewAny(): bool
    {
        return self::canModerate();
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        return $record instanceof Review
            && $user instanceof User
            && self::canModerate()
            && ($user->isSuperAdmin() || (int) $user->site_id === (int) $record->site_id);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /** @return Builder<Review> */
    public static function getEloquentQuery(): Builder
    {
        $query = Review::query();
        $user = auth()->user();

        if ($user instanceof User && $user->isSuperAdmin()) {
            return $query;
        }

        if ($user instanceof User && $user->site_id !== null) {
            return $query->where('site_id', $user->site_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('site.name')->label('Site')->sortable(),
                TextColumn::make('author_name')->label('Author')->searchable()->sortable(),
                TextColumn::make('centralProduct.name')->label('Product')->searchable()->sortable(),
                TextColumn::make('rating')->numeric()->sortable(),
                TextColumn::make('comment')->limit(80)->placeholder('No comment')->wrap(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(
                    collect(ReviewStatus::cases())
                        ->mapWithKeys(fn (ReviewStatus $status): array => [$status->value => $status->label()])
                        ->all(),
                ),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->requiresConfirmation()
                    ->visible(fn (Review $record): bool => $record->status === ReviewStatus::Pending)
                    ->action(function (Review $record): Review {
                        $user = auth()->user();

                        if (! $user instanceof User) {
                            return $record;
                        }

                        return app(ApproveReviewAction::class)->handle($user, $record);
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->visible(fn (Review $record): bool => $record->status === ReviewStatus::Pending)
                    ->schema([
                        Textarea::make('reason')
                            ->label('Rejection reason')
                            ->required()
                            ->maxLength(2000),
                    ])
                    ->action(function (array $data, Review $record): Review {
                        $user = auth()->user();

                        if (! $user instanceof User) {
                            return $record;
                        }

                        return app(RejectReviewAction::class)->handle($user, $record, (string) $data['reason']);
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
        ];
    }

    private static function canModerate(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasCatalogHubPermission('reviews.moderate');
    }
}
