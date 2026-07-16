<?php

namespace App\Filament\Resources;

use App\Actions\Corrections\CreateCorrectionRequestAction;
use App\Enums\ChangeRequestStatus;
use App\Filament\Resources\CorrectionRequestResource\Pages;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ChangeRequest;
use App\Models\SiteProduct;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class CorrectionRequestResource extends Resource
{
    protected static ?string $model = ChangeRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Site';

    protected static ?string $navigationLabel = 'Correction Requests';

    protected static ?string $modelLabel = 'Correction Request';

    protected static ?string $pluralModelLabel = 'Correction Requests';

    protected static ?string $slug = 'correction-requests';

    public static function canViewAny(): bool
    {
        return self::canRequest();
    }

    public static function canView(Model $record): bool
    {
        $user = self::authenticatedUser();

        return $record instanceof ChangeRequest
            && $user?->site_id !== null
            && (int) $record->site_id === (int) $user->site_id
            && self::canRequest();
    }

    public static function canCreate(): bool
    {
        return self::canRequest() && self::authenticatedUser()?->site_id !== null;
    }

    /** @return Builder<ChangeRequest> */
    public static function getEloquentQuery(): Builder
    {
        $user = self::authenticatedUser();

        return parent::getEloquentQuery()
            ->when(
                $user?->site_id !== null,
                fn (Builder $query): Builder => $query->where('site_id', $user->site_id),
                fn (Builder $query): Builder => $query->whereKey([]),
            );
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('central_product_id')
                ->label('Product')
                ->options(fn (): array => self::siteProductOptions())
                ->searchable()
                ->live()
                ->required(),
            Select::make('field_path')
                ->label('Canonical field')
                ->options(CreateCorrectionRequestAction::PRODUCT_FIELDS)
                ->live()
                ->required(),
            TextEntry::make('current_value_preview')
                ->label('Current central value')
                ->state(fn (Get $get): string => self::currentValuePreview(
                    $get('central_product_id'),
                    $get('field_path'),
                )),
            Textarea::make('proposed_value')
                ->label('Proposed value')
                ->required()
                ->maxLength(10000)
                ->rows(4),
            TextInput::make('evidence_url')
                ->label('Evidence URL')
                ->url()
                ->maxLength(2048),
            Textarea::make('evidence_note')
                ->label('Evidence note')
                ->maxLength(5000)
                ->rows(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('centralProduct.name')->label('Product')->searchable()->sortable(),
                TextColumn::make('field_path')->label('Field')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('reviewed_at')->dateTime()->placeholder('Not reviewed')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(
                    collect(ChangeRequestStatus::cases())
                        ->mapWithKeys(fn (ChangeRequestStatus $status): array => [$status->value => $status->label()])
                        ->all(),
                ),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCorrectionRequests::route('/'),
            'create' => Pages\CreateCorrectionRequest::route('/create'),
        ];
    }

    /** @return array<int|string, string> */
    private static function siteProductOptions(): array
    {
        $siteId = self::authenticatedUser()?->site_id;

        if ($siteId === null) {
            return [];
        }

        return CentralProduct::query()
            ->whereIn('id', SiteProduct::query()->where('site_id', $siteId)->select('central_product_id'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private static function currentValuePreview(mixed $productId, mixed $fieldPath): string
    {
        $siteId = self::authenticatedUser()?->site_id;

        if ($siteId === null || ! is_numeric($productId) || ! is_string($fieldPath) || ! array_key_exists($fieldPath, CreateCorrectionRequestAction::PRODUCT_FIELDS)) {
            return 'Select a product and field.';
        }

        $value = CentralProduct::query()
            ->whereKey((int) $productId)
            ->whereIn('id', SiteProduct::query()->where('site_id', $siteId)->select('central_product_id'))
            ->value($fieldPath);

        return $value === null || $value === '' ? 'Empty' : (string) $value;
    }

    private static function canRequest(): bool
    {
        return self::authenticatedUser()?->hasCatalogHubPermission('corrections.request') ?? false;
    }

    private static function authenticatedUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
