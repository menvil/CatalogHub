<?php

namespace App\Jobs\Pricing;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class StorePriceHistoryJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $marketOfferId,
        public bool $force = false,
    ) {}

    public function handle(): void
    {
        // Price history behavior is implemented by P17-024.
    }
}
