<?php

declare(strict_types=1);

namespace Tests\Feature\Queries;

use App\Models\MediaAsset;
use App\Queries\Media\MediaLibraryQuery;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class BrandMediaAssetSelectionQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_compatible_asset_selection_is_filtered_stable_and_server_paginated(): void
    {
        $timestamp = CarbonImmutable::parse('2026-08-27T10:00:00Z');
        $compatibleAssetIds = [];
        foreach (range(1, 8) as $index) {
            $compatibleAssetIds[] = MediaAsset::factory()->create([
                'original_filename' => "brand-candidate-{$index}.png",
                'type' => 'image',
                'status' => 'active',
                'mime_type' => 'image/png',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])->getKey();
        }
        MediaAsset::factory()->create(['type' => 'document', 'status' => 'active', 'mime_type' => 'application/pdf']);
        MediaAsset::factory()->create(['type' => 'image', 'status' => 'failed', 'mime_type' => 'image/png']);
        MediaAsset::factory()->create(['type' => 'image', 'status' => 'active', 'mime_type' => 'image/gif']);

        $query = app(MediaLibraryQuery::class);
        DB::enableQueryLog();
        $first = $query->paginateCompatibleImages('', perPage: 3, page: 1);
        foreach ($first as $candidate) {
            $candidate->variants->count();
        }
        self::assertCount(3, DB::getQueryLog(), 'Pagination and eager variant loading must remain a fixed three-query read.');
        DB::disableQueryLog();
        $second = $query->paginateCompatibleImages('', perPage: 3, page: 2);
        $search = $query->paginateCompatibleImages('candidate-2', perPage: 3, page: 1);
        $defaultPage = $query->paginateCompatibleImages('', page: 1);
        $preferredAssetId = $compatibleAssetIds[1];
        $expectedAssetIds = array_reverse($compatibleAssetIds);
        $preferred = $query->paginateCompatibleImages('', perPage: 3, page: 1, preferredAssetId: $preferredAssetId);

        self::assertSame(8, $first->total());
        self::assertSame(3, $first->count());
        self::assertSame(array_slice($expectedAssetIds, 0, 3), $first->pluck('id')->all());
        self::assertSame(array_slice($expectedAssetIds, 3, 3), $second->pluck('id')->all());
        self::assertSame([], array_values(array_intersect($first->pluck('id')->all(), $second->pluck('id')->all())));
        self::assertSame($first->pluck('id')->sortDesc()->values()->all(), $first->pluck('id')->all());
        self::assertSame($second->pluck('id')->sortDesc()->values()->all(), $second->pluck('id')->all());
        self::assertSame(['brand-candidate-2.png'], $search->pluck('original_filename')->all());
        self::assertSame(24, $defaultPage->perPage());
        self::assertSame($preferredAssetId, $preferred->first()?->getKey());
    }
}
