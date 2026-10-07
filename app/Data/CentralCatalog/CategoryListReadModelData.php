<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

use Illuminate\Pagination\LengthAwarePaginator;

final readonly class CategoryListReadModelData
{
    /**
     * @param  LengthAwarePaginator<int, CategoryListRow>  $categories
     * @param  array<int, string>  $levelOptions
     * @param  array<int, string>  $localeOptions
     * @param  array<int, string>  $siteOptions
     */
    public function __construct(public LengthAwarePaginator $categories, public CategoryListSummary $summary, public array $levelOptions, public array $localeOptions, public array $siteOptions) {}
}
