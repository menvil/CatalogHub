<?php

namespace App\Enums;

enum SiteMembershipRole: string
{
    case SiteAdmin = 'site_admin';
    case Translator = 'translator';
    case Moderator = 'moderator';
}
