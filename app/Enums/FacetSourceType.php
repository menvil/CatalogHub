<?php

namespace App\Enums;

enum FacetSourceType: string
{
    case Brand = 'brand';
    case Attribute = 'attribute';
    case Rating = 'rating';

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Brand => 'Brand',
            self::Attribute => 'Attribute',
            self::Rating => 'Rating',
        };
    }
}
