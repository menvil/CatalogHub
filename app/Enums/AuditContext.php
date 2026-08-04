<?php

namespace App\Enums;

enum AuditContext: string
{
    case System = 'system';
    case Central = 'central';
    case Site = 'site';
}
