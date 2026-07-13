<?php

namespace App\Services\Sites;

final class AllowedSiteOverrideFields
{
    /** @var list<string> */
    public const FIELDS = ['local_title', 'local_slug', 'meta_title', 'meta_description', 'intro_text', 'hero_text'];

    /** @var list<string> */
    public const ENTITY_TYPES = ['product', 'category', 'brand'];

    public function allows(string $field): bool
    {
        return in_array($field, self::FIELDS, true);
    }

    public function allowsEntityType(string $type): bool
    {
        return in_array($type, self::ENTITY_TYPES, true);
    }
}
