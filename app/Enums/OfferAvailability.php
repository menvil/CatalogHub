<?php

namespace App\Enums;

enum OfferAvailability: string
{
    case InStock = 'in_stock';
    case OutOfStock = 'out_of_stock';
    case Preorder = 'preorder';
    case Backorder = 'backorder';
    case Unknown = 'unknown';

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $availability): array => [
                $availability->value => str($availability->value)->headline()->toString(),
            ])->all();
    }
}
