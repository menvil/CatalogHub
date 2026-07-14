<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectionConflictResource\Pages;
use App\Filament\Support\ProjectionResourceSupport;
use App\Models\ProjectionConflict;
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
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class ProjectionConflictResource extends Resource
{
    protected static ?string $model = ProjectionConflict::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'Projections';

    protected static ?string $navigationLabel = 'Projection conflicts';

    public static function canViewAny(): bool
    {
        return ProjectionResourceSupport::canView();
    }

    public static function canView(Model $record): bool
    {
        return ProjectionResourceSupport::canView();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('site.name')->label('Site')->searchable(),
                TextColumn::make('locale')->badge()->placeholder('All'),
                TextColumn::make('severity')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('conflict_type')->badge()->searchable()->sortable(),
                TextColumn::make('entity_type')->sortable(),
                TextColumn::make('entity_id')->sortable(),
                TextColumn::make('message')->limit(80)->searchable()->placeholder('None'),
                TextColumn::make('last_seen_at')->dateTime()->placeholder('Unknown')->sortable(),
            ])
            ->filters([
                SelectFilter::make('site_id')->label('Site')->relationship('site', 'name'),
                SelectFilter::make('locale')->options(fn (): array => ProjectionConflict::query()
                    ->whereNotNull('locale')->distinct()->orderBy('locale')->pluck('locale', 'locale')->all()),
                SelectFilter::make('severity')->options([
                    'low' => 'Low',
                    'medium' => 'Medium',
                    'high' => 'High',
                    'critical' => 'Critical',
                ]),
                SelectFilter::make('status')->options([
                    'open' => 'Open',
                    'ignored' => 'Ignored',
                    'resolved' => 'Resolved',
                ]),
                SelectFilter::make('entity_type')->options(fn (): array => ProjectionConflict::query()
                    ->distinct()->orderBy('entity_type')->pluck('entity_type', 'entity_type')->all()),
                SelectFilter::make('conflict_type')->options(fn (): array => ProjectionConflict::query()
                    ->distinct()->orderBy('conflict_type')->pluck('conflict_type', 'conflict_type')->all()),
            ])
            ->defaultSort('last_seen_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                self::previewAction(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('site.name')->label('Site'),
            TextEntry::make('locale')->badge()->placeholder('All locales'),
            TextEntry::make('severity')->badge(),
            TextEntry::make('status')->badge(),
            TextEntry::make('conflict_type')->badge(),
            TextEntry::make('entity_type'),
            TextEntry::make('entity_id'),
            TextEntry::make('message')->placeholder('None')->columnSpanFull(),
            TextEntry::make('first_seen_at')->dateTime()->placeholder('Unknown'),
            TextEntry::make('last_seen_at')->dateTime()->placeholder('Unknown'),
            TextEntry::make('resolved_at')->dateTime()->placeholder('Unresolved'),
            TextEntry::make('context_json')
                ->label('Context')
                ->formatStateUsing(fn (mixed $state): string => ProjectionResourceSupport::prettyJson($state))
                ->copyable()
                ->columnSpanFull(),
            TextEntry::make('projection_preview')
                ->label('Projection preview')
                ->state('Open product projection')
                ->url(fn (ProjectionConflict $record): ?string => self::previewUrl($record))
                ->visible(fn (ProjectionConflict $record): bool => self::previewUrl($record) !== null),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjectionConflicts::route('/'),
            'view' => Pages\ViewProjectionConflict::route('/{record}'),
        ];
    }

    private static function previewAction(): Action
    {
        return Action::make('projectionPreview')
            ->label('Projection')
            ->icon(Heroicon::OutlinedEye)
            ->url(fn (ProjectionConflict $record): ?string => self::previewUrl($record))
            ->visible(fn (ProjectionConflict $record): bool => self::previewUrl($record) !== null);
    }

    private static function previewUrl(ProjectionConflict $record): ?string
    {
        return ProjectionResourceSupport::productPreviewUrl(
            $record->site_id,
            $record->entity_type,
            $record->entity_id,
            $record->locale,
        );
    }
}
