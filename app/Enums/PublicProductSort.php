<?php

namespace App\Enums;

enum PublicProductSort: string
{
    case Default = 'default';
    case RatingDesc = 'rating_desc';
    case Newest = 'newest';
    case NameAsc = 'name_asc';
    case NameDesc = 'name_desc';
    case PriceAsc = 'price_asc';

    public static function fromInput(mixed $value): self
    {
        return is_string($value) ? self::tryFrom($value) ?? self::Default : self::Default;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::Default->value => 'Relevance',
            self::RatingDesc->value => 'Rating',
            self::Newest->value => 'Newest',
            self::NameAsc->value => 'Name A–Z',
            self::NameDesc->value => 'Name Z–A',
            self::PriceAsc->value => 'Price: low to high',
        ];
    }
}
