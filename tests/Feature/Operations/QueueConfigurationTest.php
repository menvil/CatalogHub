<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class QueueConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_testing_queue_runtime_is_synchronous_and_failed_job_storage_is_available(): void
    {
        $connection = config('queue.default');

        $this->assertSame('sync', $connection);
        $this->assertNotNull(app(QueueFactory::class)->connection($connection));
        $this->assertContains(config('queue.failed.driver'), ['database', 'database-uuids']);
        $this->assertTrue(Schema::hasTable((string) config('queue.failed.table')));
    }

    public function test_production_template_declares_redis_as_the_asynchronous_queue_runtime(): void
    {
        $environment = file_get_contents(base_path('.env.production.example'));
        $this->assertIsString($environment);

        $this->assertMatchesRegularExpression('/^QUEUE_CONNECTION=redis$/m', $environment);
        $this->assertMatchesRegularExpression('/^QUEUE_FAILED_DRIVER=database-uuids$/m', $environment);
    }
}
