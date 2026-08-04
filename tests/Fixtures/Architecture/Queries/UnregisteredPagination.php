<?php

namespace App\Queries\ArchitectureFixtures;

use App\Models\Site;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class UnregisteredPagination
{
    /** @return LengthAwarePaginator<int, Site> */
    public function paginate(): LengthAwarePaginator
    {
        return Site::query()->orderBy('id')->paginate();
    }
}
