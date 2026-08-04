<?php

namespace App\Services\ArchitectureFixtures;

use App\Models\Site;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class OutsideBoundaryPagination
{
    /** @return LengthAwarePaginator<int, Site> */
    public function paginate(): LengthAwarePaginator
    {
        return Site::query()->orderBy('id')->paginate();
    }
}
