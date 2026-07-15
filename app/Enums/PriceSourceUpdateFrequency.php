<?php

namespace App\Enums;

enum PriceSourceUpdateFrequency: string
{
    case Manual = 'manual';
    case Hourly = 'hourly';
    case EverySixHours = 'every_6_hours';
    case Daily = 'daily';
    case Weekly = 'weekly';

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $frequency): array => [
                $frequency->value => str($frequency->value)->headline()->toString(),
            ])->all();
    }
}
