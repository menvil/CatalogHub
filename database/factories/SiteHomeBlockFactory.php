<?php

namespace Database\Factories;

use App\Enums\BlockStatus;
use App\Models\BlockDefinition;
use App\Models\Site;
use App\Models\SiteHomeBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SiteHomeBlock> */
class SiteHomeBlockFactory extends Factory
{
    protected $model = SiteHomeBlock::class;

    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'block_code' => fn (): string => BlockDefinition::factory()->create(['status' => BlockStatus::Active])->code,
            'position' => 0,
            'enabled' => true,
            'config_json' => [],
            'visibility_json' => null,
        ];
    }
}
