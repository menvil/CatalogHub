<?php

namespace App\Enums;

enum FacetType: string
{
    case Checkbox = 'checkbox';
    case Range = 'range';
    case Boolean = 'boolean';
    case Select = 'select';

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
            self::Checkbox => 'Checkbox',
            self::Range => 'Range',
            self::Boolean => 'Boolean',
            self::Select => 'Select',
        };
    }

    public function acceptsMultipleValues(): bool
    {
        return $this === self::Checkbox;
    }
}
