<?php

namespace App\Filament\Resources;

use App\Actions\Corrections\ApproveCorrectionAction;
use App\Actions\Corrections\RejectCorrectionAction;
use App\Enums\ChangeRequestStatus;
use App\Filament\Resources\ChangeRequestResource\Pages;
use App\Models\ChangeRequest;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class ChangeRequestResource extends Resource
{
    protected static ?string $model = ChangeRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static string|UnitEnum|null $navigationGroup = 'Corrections';

    protected static ?string $navigationLabel = 'Change Requests';

    protected static ?string $modelLabel = 'Change Request';

    protected static ?string $pluralModelLabel = 'Change Requests';

    protected static ?string $slug = 'change-requests';

    public static function canViewAny(): bool
    {
        return self::canReview();
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof ChangeRequest && self::canReview();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('site.name')->label('Site')->searchable()->sortable(),
                TextColumn::make('centralProduct.name')->label('Product')->searchable()->sortable(),
                TextColumn::make('field_path')->label('Field')->searchable()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('createdBy.name')->label('Created by')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('reviewed_at')->dateTime()->placeholder('Not reviewed')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(
                    collect(ChangeRequestStatus::cases())
                        ->mapWithKeys(fn (ChangeRequestStatus $status): array => [$status->value => $status->label()])
                        ->all(),
                ),
                SelectFilter::make('site_id')->label('Site')->relationship('site', 'name'),
                SelectFilter::make('central_product_id')->label('Product')->relationship('centralProduct', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->requiresConfirmation()
                    ->visible(fn (ChangeRequest $record): bool => $record->status === ChangeRequestStatus::Pending)
                    ->action(function (ChangeRequest $record): ChangeRequest {
                        $user = auth()->user();

                        return $user instanceof User
                            ? app(ApproveCorrectionAction::class)->handle($user, $record)
                            : $record;
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->visible(fn (ChangeRequest $record): bool => $record->status === ChangeRequestStatus::Pending)
                    ->schema([
                        Textarea::make('reason')
                            ->label('Rejection reason')
                            ->required()
                            ->maxLength(5000),
                    ])
                    ->action(function (array $data, ChangeRequest $record): ChangeRequest {
                        $user = auth()->user();

                        return $user instanceof User
                            ? app(RejectCorrectionAction::class)->handle($user, $record, (string) $data['reason'])
                            : $record;
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No change requests');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('site.name')->label('Source site'),
            TextEntry::make('centralProduct.name')->label('Product')->placeholder('No central product'),
            TextEntry::make('field_path')->label('Canonical field'),
            TextEntry::make('status')->badge(),
            TextEntry::make('createdBy.name')->label('Created by'),
            TextEntry::make('old_value_json')
                ->label('Current value')
                ->formatStateUsing(self::formatJson(...)),
            TextEntry::make('proposed_value_json')
                ->label('Proposed value')
                ->formatStateUsing(self::formatJson(...)),
            TextEntry::make('evidence_url')->label('Evidence URL')->url(fn (?string $state): ?string => $state)->placeholder('None'),
            TextEntry::make('evidence_note')->label('Evidence note')->placeholder('None'),
            TextEntry::make('created_at')->dateTime(),
            TextEntry::make('reviewed_at')->dateTime()->placeholder('Not reviewed'),
            TextEntry::make('rejection_reason')->label('Rejection reason')->placeholder('Not rejected'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChangeRequests::route('/'),
            'view' => Pages\ViewChangeRequest::route('/{record}'),
        ];
    }

    private static function canReview(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasCatalogHubPermission('corrections.review');
    }

    private static function formatJson(mixed $state): string
    {
        if (! is_array($state)) {
            return $state === null ? 'Empty' : (string) $state;
        }

        return json_encode(
            $state,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ) ?: 'Empty';
    }
}
