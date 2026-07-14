<?php

namespace Database\Factories;

use App\Models\FacetDefinition;
use App\Models\Site;
use App\Models\SiteFacetOverride;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SiteFacetOverride> */
class SiteFacetOverrideFactory extends Factory
{
    protected $model = SiteFacetOverride::class;

    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'facet_definition_id' => FacetDefinition::factory(),
            'label_override' => null,
            'position_override' => null,
            'is_visible' => null,
            'default_collapsed' => null,
            'config_json' => null,
        ];
    }
}
