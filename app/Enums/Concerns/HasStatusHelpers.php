<?php

namespace App\Enums\Concerns;

trait HasStatusHelpers
{
    abstract public function color(): string;

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
}
