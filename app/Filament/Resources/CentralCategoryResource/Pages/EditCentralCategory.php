<?php

namespace App\Filament\Resources\CentralCategoryResource\Pages;

use App\Enums\Permission;
use App\Filament\Resources\CentralCategoryResource;
use App\Services\Categories\CategoryAccess;
use Filament\Resources\Pages\Page;

/**
 * Retired temporary route; CA-018 owns the only Category Edit form.
 *
 * The page has no form, record state or Livewire mutation method. It authorizes
 * the same capability as the canonical route and redirects to the canonical
 * Edit for the same numeric Category ID, where strict binding applies.
 */
final class EditCentralCategory extends Page
{
    protected static string $resource = CentralCategoryResource::class;

    protected string $view = 'filament.pages.category-route-redirect';

    public function mount(int|string $record): void
    {
        abort_unless(app(CategoryAccess::class)->allows(Permission::CatalogCategoriesManage, true), 403);
        abort_unless(is_int($record) || ctype_digit($record), 404);
        $this->redirectRoute('central.categories.edit', ['category' => (int) $record]);
    }
}
