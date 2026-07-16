<?php

namespace Tests\Feature\Security;

use App\Livewire\Public\Leads\LeadForm;
use App\Livewire\Public\Reviews\ReviewForm;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Services\Security\PublicRequestRateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class PublicRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_submissions_are_rate_limited(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $service = app(PublicRequestRateLimiter::class);
        $key = $service->key('public-reviews', '127.0.0.1', [$site->id, $product->id]);

        foreach (range(1, 5) as $_) {
            RateLimiter::hit($key, 60);
        }

        Livewire::test(ReviewForm::class, ['site' => $site, 'product' => $product])
            ->call('submit')
            ->assertStatus(429);
    }

    public function test_lead_submissions_are_rate_limited(): void
    {
        $site = Site::factory()->create();
        $service = app(PublicRequestRateLimiter::class);
        $key = $service->key('public-leads', '127.0.0.1', [$site->id, null, null]);

        foreach (range(1, 3) as $_) {
            RateLimiter::hit($key, 60);
        }

        Livewire::test(LeadForm::class, ['site' => $site])
            ->call('submit')
            ->assertStatus(429);
    }

    public function test_public_search_is_rate_limited_per_ip(): void
    {
        foreach (range(1, 60) as $_) {
            $this->get('/en/search');
        }

        $this->get('/en/search')->assertTooManyRequests();
    }
}
