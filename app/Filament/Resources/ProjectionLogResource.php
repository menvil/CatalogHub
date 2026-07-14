<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectionLogResource\Pages;
use App\Filament\Support\ProjectionResourceSupport;
use App\Models\ProjectionJob;
use App\Models\ProjectionLog;
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

final class ProjectionLogResource extends Resource
{
    protected static ?string $model = ProjectionLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Projections';

    protected static ?string $navigationLabel = 'Projection logs';

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
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('site.name')->label('Site')->placeholder('Global')->searchable(),
                TextColumn::make('level')->badge()->sortable(),
                TextColumn::make('event')->badge()->searchable()->sortable(),
                TextColumn::make('message')->limit(80)->searchable()->placeholder('None'),
                TextColumn::make('entity_type')->placeholder('None'),
                TextColumn::make('entity_id')->placeholder('None'),
                TextColumn::make('job.status')->label('Job status')->badge()->placeholder('No job'),
            ])
            ->filters([
                SelectFilter::make('site_id')->label('Site')->relationship('site', 'name'),
                SelectFilter::make('level')->options([
                    'debug' => 'Debug',
                    'info' => 'Info',
                    'warning' => 'Warning',
                    'error' => 'Error',
                ]),
                SelectFilter::make('event')->options(fn (): array => ProjectionLog::query()
                    ->distinct()->orderBy('event')->pluck('event', 'event')->all()),
                SelectFilter::make('entity_type')->options(fn (): array => ProjectionLog::query()
                    ->whereNotNull('entity_type')->distinct()->orderBy('entity_type')
                    ->pluck('entity_type', 'entity_type')->all()),
                SelectFilter::make('job_status')->label('Job status')->options([
                    'pending' => 'Pending',
                    'building' => 'Building',
                    'completed' => 'Completed',
                    'failed' => 'Failed',
                ])->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                    ? $query->whereIn('projection_job_id', ProjectionJob::query()
                        ->where('status', $data['value'])->select('id'))
                    : $query),
                SelectFilter::make('locale')->options(fn (): array => ProjectionJob::query()
                    ->whereNotNull('locale')->distinct()->orderBy('locale')->pluck('locale', 'locale')->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereIn('projection_job_id', ProjectionJob::query()
                            ->where('locale', $data['value'])->select('id'))
                        : $query),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                self::previewAction(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('created_at')->dateTime(),
            TextEntry::make('site.name')->label('Site')->placeholder('Global'),
            TextEntry::make('level')->badge(),
            TextEntry::make('event')->badge(),
            TextEntry::make('message')->placeholder('None')->columnSpanFull(),
            TextEntry::make('entity_type')->placeholder('None'),
            TextEntry::make('entity_id')->placeholder('None'),
            TextEntry::make('job.uuid')->label('Projection job')->placeholder('No job'),
            TextEntry::make('job.status')->label('Job status')->badge()->placeholder('No job'),
            TextEntry::make('context_json')
                ->label('Context')
                ->formatStateUsing(fn (mixed $state): string => ProjectionResourceSupport::prettyJson($state))
                ->copyable()
                ->columnSpanFull(),
            TextEntry::make('projection_preview')
                ->label('Projection preview')
                ->state('Open product projection')
                ->url(fn (ProjectionLog $record): ?string => self::previewUrl($record))
                ->visible(fn (ProjectionLog $record): bool => self::previewUrl($record) !== null),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjectionLogs::route('/'),
            'view' => Pages\ViewProjectionLog::route('/{record}'),
        ];
    }

    private static function previewAction(): Action
    {
        return Action::make('projectionPreview')
            ->label('Projection')
            ->icon(Heroicon::OutlinedEye)
            ->url(fn (ProjectionLog $record): ?string => self::previewUrl($record))
            ->visible(fn (ProjectionLog $record): bool => self::previewUrl($record) !== null);
    }

    private static function previewUrl(ProjectionLog $record): ?string
    {
        return ProjectionResourceSupport::productPreviewUrl(
            $record->site_id,
            $record->entity_type,
            $record->entity_id,
            $record->job?->locale,
        );
    }
}
