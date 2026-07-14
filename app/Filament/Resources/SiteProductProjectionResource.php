<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteProductProjectionResource\Pages;
use App\Models\SiteProductProjection;
use App\Models\SiteSearchDocument;
use App\Models\SiteSitemapUrl;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class SiteProductProjectionResource extends Resource
{
    protected static ?string $model = SiteProductProjection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEye;

    protected static string|UnitEnum|null $navigationGroup = 'Projections';

    protected static ?string $navigationLabel = 'Projection preview';

    protected static ?string $modelLabel = 'product projection';

    protected static ?string $pluralModelLabel = 'product projections';

    protected static ?string $recordTitleAttribute = 'title';

    public static function canViewAny(): bool
    {
        return self::canViewProjections();
    }

    public static function canView(Model $record): bool
    {
        return self::canViewProjections();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('site.name')->label('Site')->searchable()->sortable(),
                TextColumn::make('locale')->badge()->sortable(),
                TextColumn::make('product.name')->label('Product')->searchable()->sortable(),
                TextColumn::make('title')->searchable()->toggleable(),
                TextColumn::make('slug')->searchable()->toggleable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('checksum')->limit(12)->copyable()->toggleable(),
                TextColumn::make('built_at')->dateTime()->sortable(),
                TextColumn::make('stale_at')->dateTime()->placeholder('Fresh')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('site_id')->label('Site')->relationship('site', 'name'),
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'building' => 'Building',
                    'active' => 'Active',
                    'stale' => 'Stale',
                    'warning' => 'Warning',
                    'failed' => 'Failed',
                ]),
                SelectFilter::make('locale')
                    ->options(fn (): array => SiteProductProjection::query()
                        ->distinct()
                        ->orderBy('locale')
                        ->pluck('locale', 'locale')
                        ->all()),
            ])
            ->defaultSort('built_at', 'desc')
            ->recordActions([ViewAction::make()]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('site.name')->label('Site'),
            TextEntry::make('locale')->badge(),
            TextEntry::make('product.name')->label('Central product'),
            TextEntry::make('status')->badge(),
            TextEntry::make('central_product_version')->label('Source version')->placeholder('Unknown'),
            TextEntry::make('checksum')->copyable()->placeholder('Missing'),
            TextEntry::make('built_at')->dateTime()->placeholder('Not built'),
            TextEntry::make('stale_at')->dateTime()->placeholder('Fresh'),
            TextEntry::make('failed_at')->dateTime()->placeholder('Not failed'),
            TextEntry::make('failure_reason')->placeholder('None')->columnSpanFull(),
            TextEntry::make('title')->label('Projected title'),
            TextEntry::make('slug')->copyable(),
            TextEntry::make('canonical_url')->copyable()->placeholder('Missing')->columnSpanFull(),
            TextEntry::make('rendered_summary')
                ->label('Rendered summary')
                ->state(fn (SiteProductProjection $record): string => self::prettyJson([
                    'product' => data_get($record->payload_json, 'product'),
                    'brand' => data_get($record->payload_json, 'brand'),
                    'category' => data_get($record->payload_json, 'category'),
                    'spec_sections_count' => count((array) data_get($record->payload_json, 'spec_sections', [])),
                ]))
                ->copyable()
                ->columnSpanFull(),
            TextEntry::make('search_document_status')
                ->label('Search document status')
                ->state(fn (SiteProductProjection $record): string => self::displayStatus(SiteSearchDocument::query()
                    ->where('site_id', $record->site_id)
                    ->where('locale', $record->locale)
                    ->where('document_type', 'product')
                    ->where('document_id', $record->central_product_id)
                    ->value('status')))
                ->badge(),
            TextEntry::make('sitemap_status')
                ->label('Sitemap status')
                ->state(fn (SiteProductProjection $record): string => self::displayStatus(SiteSitemapUrl::query()
                    ->where('site_id', $record->site_id)
                    ->where('locale', $record->locale)
                    ->where('entity_type', 'product')
                    ->where('entity_id', $record->central_product_id)
                    ->value('status')))
                ->badge(),
            TextEntry::make('sitemap_url')
                ->label('Sitemap URL')
                ->state(fn (SiteProductProjection $record): ?string => SiteSitemapUrl::query()
                    ->where('site_id', $record->site_id)
                    ->where('locale', $record->locale)
                    ->where('entity_type', 'product')
                    ->where('entity_id', $record->central_product_id)
                    ->value('url'))
                ->copyable()
                ->placeholder('Missing')
                ->columnSpanFull(),
            TextEntry::make('payload_json')
                ->label('Projection payload')
                ->formatStateUsing(fn (mixed $state): string => self::prettyJson($state))
                ->copyable()
                ->columnSpanFull(),
            TextEntry::make('seo_json')
                ->label('SEO payload')
                ->formatStateUsing(fn (mixed $state): string => self::prettyJson($state))
                ->copyable()
                ->columnSpanFull(),
            TextEntry::make('media_json')
                ->label('Media payload')
                ->formatStateUsing(fn (mixed $state): string => self::prettyJson($state))
                ->copyable()
                ->columnSpanFull(),
            TextEntry::make('search_summary_json')
                ->label('Search summary')
                ->formatStateUsing(fn (mixed $state): string => self::prettyJson($state))
                ->copyable()
                ->columnSpanFull(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiteProductProjections::route('/'),
            'view' => Pages\ViewSiteProductProjection::route('/{record}'),
        ];
    }

    private static function prettyJson(mixed $value): string
    {
        return json_encode(
            $value ?? [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    private static function displayStatus(mixed $status): string
    {
        if ($status instanceof BackedEnum) {
            return (string) $status->value;
        }

        return is_string($status) && $status !== '' ? $status : 'missing';
    }

    private static function canViewProjections(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || $user->isCentralAdmin() || $user->isCatalogEditor());
    }
}
