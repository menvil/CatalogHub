<?php

namespace App\Filament\Concerns;

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

trait ScopesImportBatch
{
    #[Url(as: 'batch')]
    public ?int $batch = null;

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->when(
                $this->batch !== null && $this->batch > 0,
                fn (Builder $query): Builder => $query->where('import_batch_id', $this->batch),
            ));
    }
}
