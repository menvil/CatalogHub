<?php

namespace App\Jobs\Pricing;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class NormalizeExternalOffersJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $priceSourceId,
        public int $priceSourceSyncLogId,
    ) {}

    public function handle(): void
    {
        // Normalization pipeline behavior is implemented by P17-021.
    }
}
