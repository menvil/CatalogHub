<?php

namespace Database\Seeders;

use App\Enums\BlockStatus;
use App\Models\BlockDefinition;
use Illuminate\Database\Seeder;
use RuntimeException;

class BlockRegistrySeeder extends Seeder
{
    public function run(): void
    {
        $definitions = config('cataloghub_blocks');
        if (! is_array($definitions)) {
            throw new RuntimeException('CatalogHub block registry configuration must be an array.');
        }

        foreach ($definitions as $code => $definition) {
            if (! is_string($code) || ! is_array($definition)) {
                throw new RuntimeException('Every CatalogHub block definition must use a string code and array definition.');
            }

            BlockDefinition::query()->updateOrCreate(
                ['code' => $code],
                [...$definition, 'status' => BlockStatus::Active],
            );
        }
    }
}
