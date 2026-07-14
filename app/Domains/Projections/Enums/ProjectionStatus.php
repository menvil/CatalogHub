<?php

namespace App\Domains\Projections\Enums;

enum ProjectionStatus: string
{
    case Pending = 'pending';
    case Building = 'building';
    case Active = 'active';
    case Stale = 'stale';
    case Warning = 'warning';
    case Failed = 'failed';
}
