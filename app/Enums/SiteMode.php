<?php

namespace App\Enums;

enum SiteMode: string
{
    case SingleCategory = 'single_category';
    case MultiCategory = 'multi_category';
}
