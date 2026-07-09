<?php

namespace App\Enums;

enum CategorySchemaIssueSeverity: string
{
    case Warning = 'warning';
    case Error = 'error';
}
