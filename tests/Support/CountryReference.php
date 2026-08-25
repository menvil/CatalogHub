<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Geography\Country;
use RuntimeException;

final class CountryReference
{
    public static function get(string $alpha2): Country
    {
        $country = Country::query()->where('alpha2', $alpha2)->first();

        if (! $country instanceof Country) {
            throw new RuntimeException("Reference Country {$alpha2} is not provisioned.");
        }

        return $country;
    }

    public static function id(string $alpha2): int
    {
        return (int) self::get($alpha2)->getKey();
    }

    private function __construct() {}
}
