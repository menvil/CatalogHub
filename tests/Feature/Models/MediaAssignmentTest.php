<?php

namespace Tests\Feature\Models;

use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_media_asset(): void
    {
        $asset = MediaAsset::factory()->create();

        $assignment = MediaAssignment::factory()->for($asset, 'asset')->create([
            'entity_type' => 'central_product',
            'entity_id' => 123,
            'role' => 'main',
        ]);

        $this->assertTrue($assignment->asset->is($asset));
    }

    public function test_entity_role_and_locale_scopes(): void
    {
        $match = MediaAssignment::factory()->create([
            'entity_type' => 'central_product',
            'entity_id' => 123,
            'role' => 'hero',
            'locale' => 'de-DE',
        ]);
        MediaAssignment::factory()->create([
            'entity_type' => 'central_product',
            'entity_id' => 124,
            'role' => 'hero',
            'locale' => 'de-DE',
        ]);

        $resolved = MediaAssignment::query()
            ->forEntity('central_product', 123)
            ->forRole('hero')
            ->forLocale('de-DE')
            ->first();

        $this->assertTrue($resolved?->is($match));
    }
}
