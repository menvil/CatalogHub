<?php

namespace App\Enums;

enum PriceSourceType: string
{
    case Manual = 'manual';
    case CsvFeed = 'csv_feed';
    case Api = 'api';
    case Widget = 'widget';

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
