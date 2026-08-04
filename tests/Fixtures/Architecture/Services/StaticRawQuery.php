<?php

namespace App\Services\ArchitectureFixtures;

use App\Models\SiteSearchDocument;

final class StaticRawQuery
{
    public function query(): void
    {
        SiteSearchDocument::whereRaw('id = 1')->get();
    }
}
