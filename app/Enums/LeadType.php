<?php

namespace App\Enums;

enum LeadType: string
{
    case Repair = 'repair';
    case BuyingAdvice = 'buying_advice';
    case SellDevice = 'sell_device';
    case AccessoryRequest = 'accessory_request';
    case BusinessInquiry = 'business_inquiry';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Repair => 'Repair',
            self::BuyingAdvice => 'Buying advice',
            self::SellDevice => 'Sell a device',
            self::AccessoryRequest => 'Accessory request',
            self::BusinessInquiry => 'Business inquiry',
            self::Other => 'Other',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }
}
