<?php

namespace App\Enums;

enum OfferCondition: string
{
    case New = 'new';
    case Used = 'used';
    case Refurbished = 'refurbished';
    case Unknown = 'unknown';

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $condition): array => [
                $condition->value => str($condition->value)->headline()->toString(),
            ])->all();
    }
}
