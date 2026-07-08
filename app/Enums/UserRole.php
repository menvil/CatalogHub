<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case CentralAdmin = 'central_admin';
    case CatalogEditor = 'catalog_editor';
    case SiteAdmin = 'site_admin';
    case Translator = 'translator';
    case Moderator = 'moderator';

    public static function default(): self
    {
        return self::CatalogEditor;
    }
}
