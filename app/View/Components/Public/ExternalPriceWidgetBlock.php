<?php

namespace App\View\Components\Public;

use App\Data\Pricing\ExternalPriceWidgetData;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class ExternalPriceWidgetBlock extends Component
{
    public function __construct(public ExternalPriceWidgetData $widget) {}

    public function render(): View
    {
        return view('components.public.offers.external-price-widget-block');
    }
}
