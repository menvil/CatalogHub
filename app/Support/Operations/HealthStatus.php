<?php

declare(strict_types=1);

namespace App\Support\Operations;

enum HealthStatus: string
{
    case Healthy = 'healthy';
    case Failed = 'failed';
    case Stale = 'stale';
    case Unavailable = 'unavailable';
}
