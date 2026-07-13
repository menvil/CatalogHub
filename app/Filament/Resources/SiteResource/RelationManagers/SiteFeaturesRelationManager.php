<?php

namespace App\Filament\Resources\SiteResource\RelationManagers;

use App\Models\SiteFeature;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class SiteFeaturesRelationManager extends RelationManager
{
    protected static string $relationship = 'features';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('feature_key')->options(array_combine(SiteFeature::KEYS, array_map(fn (string $key): string => str($key)->headline()->toString(), SiteFeature::KEYS)))->required()->disabledOn('edit'),
            Toggle::make('is_enabled'), KeyValue::make('config_json'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->recordTitleAttribute('feature_key')->columns([TextColumn::make('feature_key')->badge(), IconColumn::make('is_enabled')->boolean()])->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
