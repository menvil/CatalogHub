<?php

declare(strict_types=1);

namespace Tests\Feature\Observability;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Models\AuditLogEntry;
use App\Services\Audit\AuditRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class RequestCorrelationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/__foundation-correlation', static function (AuditRecorder $recorder) {
            Log::info('Correlation fixture reached.');
            $entry = $recorder->record(AuditAction::Login, AuditContext::System);

            return response()->json(['audit_id' => $entry->getKey()]);
        });
    }

    public function test_valid_request_id_correlates_response_logs_and_audit_entry(): void
    {
        $contexts = [];
        Event::listen(MessageLogged::class, static function (MessageLogged $event) use (&$contexts): void {
            if ($event->message === 'Correlation fixture reached.') {
                $contexts[] = $event->context;
            }
        });

        $requestId = 'request-correlation-123';
        $this->withHeader('X-Request-ID', $requestId)
            ->getJson('/__foundation-correlation')
            ->assertOk()
            ->assertHeader('X-Request-ID', $requestId);

        $this->assertSame($requestId, AuditLogEntry::query()->sole()->request_id);
        $this->assertSame([['request_id' => $requestId]], $contexts);
    }

    public function test_invalid_inbound_request_id_is_replaced_before_it_reaches_logs_or_audit(): void
    {
        $contexts = [];
        Event::listen(MessageLogged::class, static function (MessageLogged $event) use (&$contexts): void {
            if ($event->message === 'Correlation fixture reached.') {
                $contexts[] = $event->context;
            }
        });

        $response = $this->withHeader('X-Request-ID', 'untrusted value!')
            ->getJson('/__foundation-correlation')
            ->assertOk();

        $requestId = $response->headers->get('X-Request-ID');
        $this->assertIsString($requestId);
        $this->assertMatchesRegularExpression('/\A[0-9a-f-]{36}\z/i', $requestId);
        $this->assertSame($requestId, AuditLogEntry::query()->sole()->request_id);
        $this->assertSame([['request_id' => $requestId]], $contexts);
    }
}
