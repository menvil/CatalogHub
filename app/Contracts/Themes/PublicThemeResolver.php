<?php

declare(strict_types=1);

namespace App\Contracts\Themes;

use App\Support\Sites\SiteRuntimeContext;
use App\Support\Themes\PublicThemeContext;

interface PublicThemeResolver
{
    public function resolve(SiteRuntimeContext $site): PublicThemeContext;
}
