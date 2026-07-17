<?php

namespace App\Filament\Resources;

use App\Actions\Leads\UpdateLeadStatusAction;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Filament\Resources\LeadResource\Pages;
use App\Models\Lead;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Site';

    protected static ?string $navigationLabel = 'Leads';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', Lead::class) === true;
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        return $record instanceof Lead
            && $user instanceof User
            && $user->can('view', $record);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /** @return Builder<Lead> */
    public static function getEloquentQuery(): Builder
    {
        $query = Lead::query();
        $user = auth()->user();

        if ($user instanceof User && $user->can('system.super-admin')) {
            return $query;
        }

        if ($user instanceof User && $user->site_id !== null) {
            return $query->where('site_id', $user->site_id);
        }

        return $query->whereKey([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('site.name')->label('Site')->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('centralProduct.name')->label('Product')->placeholder('None')->searchable(),
                TextColumn::make('centralCategory.name')->label('Category')->placeholder('None')->searchable(),
                TextColumn::make('email')->copyable()->placeholder('None'),
                TextColumn::make('phone')->copyable()->placeholder('None'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(LeadStatus::options()),
                SelectFilter::make('type')->options(LeadType::options()),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(filled($data['from'] ?? null), fn (Builder $query): Builder => $query->whereDate('created_at', '>=', $data['from']))
                        ->when(filled($data['until'] ?? null), fn (Builder $query): Builder => $query->whereDate('created_at', '<=', $data['until']))),
            ])
            ->recordActions([
                Action::make('updateStatus')
                    ->label('Update status')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->fillForm(fn (Lead $record): array => ['status' => $record->status->value])
                    ->schema([
                        Select::make('status')
                            ->options(LeadStatus::options())
                            ->required(),
                    ])
                    ->action(function (array $data, Lead $record): Lead {
                        $user = auth()->user();

                        if (! $user instanceof User) {
                            return $record;
                        }

                        return app(UpdateLeadStatusAction::class)->handle(
                            $user,
                            $record,
                            LeadStatus::from((string) $data['status']),
                        );
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
        ];
    }
}
