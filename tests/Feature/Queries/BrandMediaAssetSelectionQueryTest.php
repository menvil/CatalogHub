<?php

declare(strict_types=1);

namespace Tests\Feature\Queries;

use App\Models\MediaAsset;
use App\Queries\Media\MediaLibraryQuery;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BrandMediaAssetSelectionQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_compatible_asset_selection_is_filtered_stable_and_server_paginated(): void
    {
        $timestamp = CarbonImmutable::parse('2026-08-27T10:00:00Z');
        foreach (range(1, 8) as $index) {
            MediaAsset::factory()->create([
                'original_filename' => "brand-candidate-{$index}.png",
                'type' => 'image',
                'status' => 'active',
                'mime_type' => 'image/png',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }
        MediaAsset::factory()->create(['type' => 'document', 'status' => 'active', 'mime_type' => 'application/pdf']);
        MediaAsset::factory()->create(['type' => 'image', 'status' => 'failed', 'mime_type' => 'image/png']);
        MediaAsset::factory()->create(['type' => 'image', 'status' => 'active', 'mime_type' => 'image/gif']);

        $query = app(MediaLibraryQuery::class);
        $first = $query->paginateCompatibleImages('', perPage: 3, page: 1);
        $second = $query->paginateCompatibleImages('', perPage: 3, page: 2);
        $search = $query->paginateCompatibleImages('candidate-2', perPage: 3, page: 1);

        self::assertSame(8, $first->total());
        self::assertSame(3, $first->count());
        self::assertSame([8, 7, 6], $first->pluck('id')->all());
        self::assertSame([5, 4, 3], $second->pluck('id')->all());
        self::assertSame([], array_values(array_intersect($first->pluck('id')->all(), $second->pluck('id')->all())));
        self::assertSame($first->pluck('id')->sortDesc()->values()->all(), $first->pluck('id')->all());
        self::assertSame($second->pluck('id')->sortDesc()->values()->all(), $second->pluck('id')->all());
        self::assertSame(['brand-candidate-2.png'], $search->pluck('original_filename')->all());
    }
}
