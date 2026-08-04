<?php

declare(strict_types=1);

namespace App\Enums;

enum SiteDomainType: string
{
    case Primary = 'primary';
    case Alias = 'alias';
    case Preview = 'preview';
}
