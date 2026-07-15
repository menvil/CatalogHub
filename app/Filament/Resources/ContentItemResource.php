<?php

namespace App\Filament\Resources;

use App\Enums\ContentType;
use App\Filament\Resources\ContentItemResource\Pages;
use App\Filament\Resources\ContentItemResource\RelationManagers\RelationsRelationManager;
use App\Filament\Resources\ContentItemResource\RelationManagers\TranslationsRelationManager;
use App\Models\ContentItem;
use App\Models\User;
use App\Support\Content\ContentForm;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class ContentItemResource extends Resource
{
    protected static ?string $model = ContentItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Site';

    protected static ?string $navigationLabel = 'Content';

    public static function canViewAny(): bool
    {
        return self::canManage();
    }

    public static function canCreate(): bool
    {
        return self::canManage();
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return $record instanceof ContentItem
            && $user instanceof User
            && self::canManage()
            && ($user->isSuperAdmin() || (int) $user->site_id === (int) $record->site_id);
    }

    /** @return Builder<ContentItem> */
    public static function getEloquentQuery(): Builder
    {
        $query = ContentItem::query();
        $user = auth()->user();

        if ($user instanceof User && $user->isSuperAdmin()) {
            return $query;
        }

        if ($user instanceof User && $user->site_id !== null) {
            return $query->where('site_id', $user->site_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('site_id')
                ->relationship('site', 'name')
                ->default(fn (): ?int => self::authenticatedUser()?->site_id)
                ->disabled(fn (): bool => ! (self::authenticatedUser()?->isSuperAdmin() ?? false))
                ->dehydrated()
                ->required(),
            Select::make('type')
                ->options(ContentType::options())
                ->live()
                ->required(),
            Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                    'archived' => 'Archived',
                ])
                ->default('draft')
                ->required(),
            DateTimePicker::make('published_at'),
            ...ContentForm::translationFields(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('translations.title')->label('Title')->limit(60)->placeholder('No translation'),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('site.name')->label('Site')->sortable(),
                TextColumn::make('translations.locale')->label('Locales')->badge(),
                TextColumn::make('published_at')->dateTime()->placeholder('Not published')->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(ContentType::options()),
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                    'archived' => 'Archived',
                ]),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContentItems::route('/'),
            'create' => Pages\CreateContentItem::route('/create'),
            'edit' => Pages\EditContentItem::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [TranslationsRelationManager::class, RelationsRelationManager::class];
    }

    private static function canManage(): bool
    {
        return self::authenticatedUser()?->hasCatalogHubPermission('site.content.manage') ?? false;
    }

    private static function authenticatedUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
