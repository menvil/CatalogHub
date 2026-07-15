<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MarketOfferUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $marketOfferId) {}
}
