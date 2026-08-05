<?php

declare(strict_types=1);

namespace App\View\Components\SiteAdmin;

use App\Enums\SiteStatus;
use App\Support\Sites\SiteRuntimeContext;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class SiteContextHeader extends Component
{
    public function __construct(public readonly SiteRuntimeContext $context) {}

    public function statusVariant(): string
    {
        return match ($this->context->site->status) {
            SiteStatus::Active => 'success',
            SiteStatus::Suspended => 'warning',
            SiteStatus::Archived => 'danger',
            SiteStatus::Draft => 'neutral',
        };
    }

    public function render(): View
    {
        return view('components.site-admin.site-context-header');
    }
}
