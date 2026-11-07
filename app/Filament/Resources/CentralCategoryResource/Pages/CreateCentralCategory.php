<?php

namespace App\Filament\Resources\CentralCategoryResource\Pages;

use App\Filament\Resources\CentralCategoryResource;
use Filament\Resources\Pages\Page;

/**
 * Retired temporary route; CA-018 owns the only Category Create form.
 *
 * The page has no form, state or Livewire mutation method: it authorizes the
 * same capability as the canonical route and redirects there.
 */
final class CreateCentralCategory extends Page
{
    protected static string $resource = CentralCategoryResource::class;

    protected string $view = 'filament.pages.category-route-redirect';

    public function mount(): void
    {
        abort_unless(CentralCategoryResource::canCreate(), 403);
        $this->redirectRoute('central.categories.create');
    }
}
