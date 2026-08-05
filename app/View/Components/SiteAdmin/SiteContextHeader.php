<?php

declare(strict_types=1);

namespace App\View\Components\SiteAdmin;

use App\Support\Sites\SiteRuntimeContext;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class SiteContextHeader extends Component
{
    public function __construct(public readonly SiteRuntimeContext $context) {}

    public function statusVariant(): string
    {
        $color = $this->context->site->status->color();

        return $color === 'gray' ? 'neutral' : $color;
    }

    public function render(): View
    {
        return view('components.site-admin.site-context-header');
    }
}
