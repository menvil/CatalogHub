<?php

namespace App\Actions\Sites;

use App\Models\Site;
use App\Models\SiteFeature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class UpdateSiteFeaturesAction
{
    /** @param array<string, array{is_enabled: bool, config_json?: array<string, mixed>|null}> $features */
    public function handle(Site $site, array $features): void
    {
        $invalid = array_diff(array_keys($features), SiteFeature::KEYS);
        if ($invalid !== []) {
            throw ValidationException::withMessages(['features' => 'Unknown site feature: '.reset($invalid)]);
        }

        Validator::make(['features' => $features], [
            'features' => ['array'],
            'features.*' => ['required', 'array:is_enabled,config_json'],
            'features.*.is_enabled' => ['required', 'boolean:strict'],
            'features.*.config_json' => ['nullable', 'array'],
        ])->validate();

        DB::transaction(function () use ($site, $features): void {
            $lockedSite = Site::query()->whereKey($site->getKey())->lockForUpdate()->firstOrFail();

            foreach ($features as $key => $data) {
                $values = ['is_enabled' => $data['is_enabled']];

                if (array_key_exists('config_json', $data)) {
                    $values['config_json'] = $data['config_json'];
                }

                $lockedSite->features()->updateOrCreate(['feature_key' => $key], $values);
            }
        });
    }
}
