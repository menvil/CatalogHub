<?php

declare(strict_types=1);

namespace Tests\Feature\Errors;

use App\Exceptions\Domain\AuthenticationRequiredException;
use App\Exceptions\Domain\AuthorizationDeniedException;
use App\Exceptions\Domain\InvalidInputException;
use App\Exceptions\Domain\ResourceConflictException;
use App\Exceptions\Domain\ResourceNotFoundException;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ExceptionMappingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/__foundation-domain/{status}', static function (int $status): never {
            throw self::exceptionFor($status);
        });
        Route::get('/api/__foundation-domain/{status}', static function (int $status): never {
            throw self::exceptionFor($status);
        });
    }

    #[DataProvider('statuses')]
    public function test_domain_exceptions_have_deterministic_html_statuses_without_internal_messages(int $status): void
    {
        $this->withHeader('X-Request-ID', 'domain-html-'.$status)
            ->get('/__foundation-domain/'.$status)
            ->assertStatus($status)
            ->assertHeader('X-Request-ID', 'domain-html-'.$status)
            ->assertDontSee('internal diagnostic');
    }

    #[DataProvider('statuses')]
    public function test_domain_exceptions_have_safe_api_responses(int $status, string $message): void
    {
        $this->withHeader('X-Request-ID', 'domain-api-'.$status)
            ->getJson('/api/__foundation-domain/'.$status)
            ->assertStatus($status)
            ->assertHeader('X-Request-ID', 'domain-api-'.$status)
            ->assertExactJson([
                'message' => $message,
                'request_id' => 'domain-api-'.$status,
            ])
            ->assertDontSee('internal diagnostic');
    }

    /** @return array<string, array{int, string}> */
    public static function statuses(): array
    {
        return [
            'invalid input' => [422, 'The request is invalid.'],
            'authentication required' => [401, 'Authentication is required.'],
            'authorization denied' => [403, 'You are not authorized to perform this action.'],
            'not found' => [404, 'The requested resource was not found.'],
            'conflict' => [409, 'The request conflicts with the current state.'],
        ];
    }

    private static function exceptionFor(int $status): \Throwable
    {
        return match ($status) {
            422 => new InvalidInputException('internal diagnostic'),
            401 => new AuthenticationRequiredException('internal diagnostic'),
            403 => new AuthorizationDeniedException('internal diagnostic'),
            404 => new ResourceNotFoundException('internal diagnostic'),
            409 => new ResourceConflictException('internal diagnostic'),
            default => throw new \InvalidArgumentException("Unsupported status [{$status}]."),
        };
    }
}
