<?php

namespace App\Enums;

enum CentralProductVariantStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public static function default(): self
    {
        return self::Draft;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [
                $status->value => $status->label(),
            ])
            ->all();
    }

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
