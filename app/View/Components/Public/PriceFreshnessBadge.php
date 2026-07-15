<?php

namespace App\View\Components\Public;

use App\Enums\PriceFreshnessStatus;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class PriceFreshnessBadge extends Component
{
    public PriceFreshnessStatus $status;

    public string $label;

    public string $classes;

    public function __construct(PriceFreshnessStatus|string $status)
    {
        $this->status = is_string($status)
            ? PriceFreshnessStatus::tryFrom($status) ?? PriceFreshnessStatus::Unknown
            : $status;
        [$this->label, $this->classes] = match ($this->status) {
            PriceFreshnessStatus::Fresh => ['Updated recently', 'bg-emerald-50 text-emerald-800 ring-emerald-200'],
            PriceFreshnessStatus::Stale => ['Price may be outdated', 'bg-amber-50 text-amber-800 ring-amber-200'],
            PriceFreshnessStatus::Expired => ['Outdated price', 'bg-red-50 text-red-800 ring-red-200'],
            PriceFreshnessStatus::Unknown => ['Update time unknown', 'bg-slate-100 text-slate-700 ring-slate-200'],
        };
    }

    public function render(): View
    {
        return view('components.public.offers.price-freshness-badge');
    }
}
