<?php

namespace Tests\Unit\Models;

use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_visibility_returns_only_approved_reviews(): void
    {
        Review::factory()->pending()->create();
        $approved = Review::factory()->approved()->create();
        Review::factory()->rejected()->create();
        Review::factory()->spam()->create();

        $this->assertSame(
            [$approved->id],
            Review::query()->visiblePublicly()->pluck('id')->all(),
        );
    }

    public function test_moderation_status_scopes_return_the_expected_reviews(): void
    {
        Review::factory()->pending()->create();
        $rejected = Review::factory()->rejected()->create();
        $spam = Review::factory()->spam()->create();

        $this->assertSame([$rejected->id], Review::query()->rejected()->pluck('id')->all());
        $this->assertSame([$spam->id], Review::query()->spam()->pluck('id')->all());
    }
}
