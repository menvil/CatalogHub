<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectionJobResource\Pages;
use App\Filament\Support\ProjectionResourceSupport;
use App\Models\ProjectionConflict;
use App\Models\ProjectionJob;
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

final class ProjectionJobResource extends Resource
{
    protected static ?string $model = ProjectionJob::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Projections';

    protected static ?string $navigationLabel = 'Projection jobs';

    protected static ?string $recordTitleAttribute = 'uuid';

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
                TextColumn::make('id')->label('Job')->sortable(),
                TextColumn::make('site.name')->label('Site')->placeholder('Global')->searchable(),
                TextColumn::make('job_type')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('target_type')->placeholder('None'),
                TextColumn::make('target_id')->placeholder('None'),
                TextColumn::make('locale')->badge()->placeholder('All'),
                TextColumn::make('attempts')->numeric()->sortable(),
                TextColumn::make('started_at')->dateTime()->placeholder('Not started')->sortable(),
                TextColumn::make('finished_at')->dateTime()->placeholder('Not finished')->sortable(),
            ])
            ->filters([
                SelectFilter::make('site_id')->label('Site')->relationship('site', 'name'),
                SelectFilter::make('job_type')->options(fn (): array => ProjectionJob::query()
                    ->distinct()->orderBy('job_type')->pluck('job_type', 'job_type')->all()),
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'building' => 'Building',
                    'completed' => 'Completed',
                    'failed' => 'Failed',
                ]),
                SelectFilter::make('locale')->options(fn (): array => ProjectionJob::query()
                    ->whereNotNull('locale')->distinct()->orderBy('locale')->pluck('locale', 'locale')->all()),
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
            TextEntry::make('uuid')->copyable(),
            TextEntry::make('site.name')->label('Site')->placeholder('Global'),
            TextEntry::make('job_type')->badge(),
            TextEntry::make('status')->badge(),
            TextEntry::make('target_type')->placeholder('None'),
            TextEntry::make('target_id')->placeholder('None'),
            TextEntry::make('locale')->badge()->placeholder('All locales'),
            TextEntry::make('attempts')->numeric(),
            TextEntry::make('started_at')->dateTime()->placeholder('Not started'),
            TextEntry::make('finished_at')->dateTime()->placeholder('Not finished'),
            TextEntry::make('failed_at')->dateTime()->placeholder('Not failed'),
            TextEntry::make('failure_reason')->placeholder('None')->columnSpanFull(),
            TextEntry::make('payload_json')
                ->label('Job payload')
                ->formatStateUsing(fn (mixed $state): string => ProjectionResourceSupport::prettyJson($state))
                ->copyable()
                ->columnSpanFull(),
            TextEntry::make('recent_logs')
                ->label('Recent logs')
                ->state(fn (ProjectionJob $record): string => ProjectionResourceSupport::prettyJson(
                    $record->logs()->latest('created_at')->limit(25)
                        ->get(['level', 'event', 'message', 'entity_type', 'entity_id', 'context_json', 'created_at'])
                        ->toArray(),
                ))
                ->columnSpanFull(),
            TextEntry::make('open_conflicts')
                ->label('Open conflicts for target')
                ->state(fn (ProjectionJob $record): int => ProjectionConflict::query()
                    ->where('site_id', $record->site_id)
                    ->where('entity_type', $record->target_type)
                    ->where('entity_id', $record->target_id)
                    ->where('status', 'open')
                    ->count()),
            TextEntry::make('projection_preview')
                ->label('Projection preview')
                ->state('Open product projection')
                ->url(fn (ProjectionJob $record): ?string => self::previewUrl($record))
                ->visible(fn (ProjectionJob $record): bool => self::previewUrl($record) !== null),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjectionJobs::route('/'),
            'view' => Pages\ViewProjectionJob::route('/{record}'),
        ];
    }

    private static function previewAction(): Action
    {
        return Action::make('projectionPreview')
            ->label('Projection')
            ->icon(Heroicon::OutlinedEye)
            ->url(fn (ProjectionJob $record): ?string => self::previewUrl($record))
            ->visible(fn (ProjectionJob $record): bool => self::previewUrl($record) !== null);
    }

    private static function previewUrl(ProjectionJob $record): ?string
    {
        return ProjectionResourceSupport::productPreviewUrl(
            $record->site_id,
            $record->target_type,
            $record->target_id,
            $record->locale,
        );
    }
}
