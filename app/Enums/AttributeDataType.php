<?php

namespace App\Enums;

enum AttributeDataType: string
{
    case String = 'string';
    case Text = 'text';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Boolean = 'boolean';
    case Enum = 'enum';
    case MultiEnum = 'multi_enum';
    case Json = 'json';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::String => 'String',
            self::Text => 'Text',
            self::Integer => 'Integer',
            self::Decimal => 'Decimal',
            self::Boolean => 'Boolean',
            self::Enum => 'Enum',
            self::MultiEnum => 'Multi enum',
            self::Json => 'JSON',
        };
    }

    public function allowsOptions(): bool
    {
        return in_array($this, [self::Enum, self::MultiEnum], true);
    }
}
