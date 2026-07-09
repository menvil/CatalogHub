<?php

namespace App\Enums;

enum CentralBrandStatus: string
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

    public static function colorFor(self|string|null $status): string
    {
        if (! $status instanceof self) {
            $status = self::tryFrom((string) $status);
        }

        return $status?->color() ?? 'gray';
    }

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Active => 'success',
            self::Archived => 'danger',
        };
    }
}
