<?php

declare(strict_types=1);

namespace App\Jobs\Middleware;

use App\Support\Jobs\JobContext;
use Closure;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Facades\Log;

final readonly class ApplyJobContext
{
    public function __construct(private JobContext $context) {}

    /**
     * @template TResult
     *
     * @param  Closure(Job): TResult  $next
     * @return TResult
     */
    public function handle(Job $job, Closure $next): mixed
    {
        Log::shareContext($this->context->logContext($job->getJobId()));

        try {
            return $next($job);
        } finally {
            Log::withoutContext(array_keys($this->context->logContext($job->getJobId())));
            Log::flushSharedContext();
        }
    }
}
