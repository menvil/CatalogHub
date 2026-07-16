<?php

namespace App\Services\Security;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

final class PublicRequestRateLimiter
{
    /** @var array<string, array{max: int, decay: int}> */
    private const LIMITS = [
        'public-reviews' => ['max' => 5, 'decay' => 60],
        'public-leads' => ['max' => 3, 'decay' => 60],
        'public-search' => ['max' => 60, 'decay' => 60],
        'public-contact' => ['max' => 3, 'decay' => 60],
    ];

    public function __construct(private readonly RateLimiter $limiter) {}

    /**
     * @param  list<int|string|null>  $context
     */
    public function consume(string $name, array $context = []): void
    {
        $definition = self::definition($name);
        $key = $this->key($name, request()->ip() ?? 'unknown', $context);

        if ($this->limiter->tooManyAttempts($key, $definition['max'])) {
            $retryAfter = $this->limiter->availableIn($key);

            throw new ThrottleRequestsException(
                'Too many requests. Please try again later.',
                headers: ['Retry-After' => (string) $retryAfter],
            );
        }

        $this->limiter->hit($key, $definition['decay']);
    }

    /**
     * @param  list<int|string|null>  $context
     */
    public function key(string $name, string $ip, array $context = []): string
    {
        $identity = implode('|', [$ip, ...array_map(
            static fn (int|string|null $value): string => (string) $value,
            $context,
        )]);

        return "cataloghub:{$name}:".hash('sha256', $identity);
    }

    /** @return array{max: int, decay: int} */
    public static function definition(string $name): array
    {
        return self::LIMITS[$name] ?? throw new \InvalidArgumentException("Unknown rate limiter [{$name}].");
    }
}
