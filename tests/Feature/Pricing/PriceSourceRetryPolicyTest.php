<?php

namespace Tests\Feature\Pricing;

use App\Enums\PriceSourceType;
use App\Jobs\Pricing\FetchExternalOffersJob;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Pricing\PriceSourceAdapterRegistry;
use App\Services\Pricing\PriceSourceRetryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class PriceSourceRetryPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_respects_configured_retry_limit_and_backoff(): void
    {
        $source = PriceSource::factory()->create([
            'config_json' => ['max_retries' => 2, 'retry_delays' => [10, 30]],
        ]);
        $policy = app(PriceSourceRetryPolicy::class);

        $this->assertTrue($policy->shouldRetry($source, 1));
        $this->assertTrue($policy->shouldRetry($source, 2));
        $this->assertFalse($policy->shouldRetry($source, 3));
        $this->assertSame(3, $policy->maxAttempts($source));
        $this->assertSame([10, 30], $policy->backoff($source));

        $job = new FetchExternalOffersJob($source->id, 999);
        $this->assertSame(3, $job->tries());
        $this->assertSame([10, 30], $job->backoff());
    }

    public function test_does_not_retry_permanent_configuration_errors(): void
    {
        $source = PriceSource::factory()->create(['config_json' => ['max_retries' => 3]]);
        $policy = app(PriceSourceRetryPolicy::class);

        $this->assertFalse($policy->shouldRetry($source, 1, new InvalidArgumentException('Invalid config.')));
        $this->assertTrue($policy->shouldRetry($source, 1, new RuntimeException('Temporary failure.')));
    }

    public function test_job_records_attempt_and_fails_non_retryable_configuration_error(): void
    {
        $source = PriceSource::factory()->create([
            'type' => PriceSourceType::Widget,
            'config_json' => ['max_retries' => 3],
        ]);
        $log = PriceSourceSyncLog::factory()->for($source)->create();
        $job = (new FetchExternalOffersJob($source->id, $log->id))->withFakeQueueInteractions();

        try {
            $job->handle(app(PriceSourceAdapterRegistry::class));
            $this->fail('Expected permanent adapter configuration error.');
        } catch (InvalidArgumentException) {
            $job->assertFailed();
            $attempt = $log->fresh()->metadata['retry_attempts'][0];
            $this->assertSame(1, $attempt['attempt']);
            $this->assertSame('fetch', $attempt['stage']);
            $this->assertFalse($attempt['will_retry']);
        }
    }
}
