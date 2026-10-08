<?php

namespace App\Filament\Resources\CentralCategoryResource\Pages;

use App\Filament\Resources\CentralCategoryResource;
use Filament\Resources\Pages\Page;

/** Temporary route compatibility only; CA-016 owns the sole list. */
final class ListCentralCategories extends Page
{
    protected static string $resource = CentralCategoryResource::class;

    protected string $view = 'filament.pages.category-list-redirect';

    public function mount(): void
    {
        abort_unless(CentralCategoryResource::canViewAny(), 403);
        $this->redirectRoute('central.categories.index', request()->query());
    }
}
