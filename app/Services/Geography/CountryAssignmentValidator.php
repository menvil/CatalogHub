<?php

declare(strict_types=1);

namespace App\Services\Geography;

use App\Models\Geography\Country;
use Illuminate\Validation\ValidationException;

final class CountryAssignmentValidator
{
    public function lockActive(int $countryId): Country
    {
        $country = Country::query()
            ->active()
            ->whereKey($countryId)
            ->lockForUpdate()
            ->first();

        if ($country === null) {
            throw ValidationException::withMessages([
                'country_id' => 'The selected Country is not available for new assignments.',
            ]);
        }

        return $country;
    }
}
