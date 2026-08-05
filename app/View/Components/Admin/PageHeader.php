<?php

declare(strict_types=1);

namespace App\View\Components\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use InvalidArgumentException;

final class PageHeader extends Component
{
    /**
     * @param  list<array{label: string, url?: string|null}>  $breadcrumbs
     */
    public function __construct(
        public readonly string $screenId,
        public readonly string $title,
        public readonly ?string $description = null,
        public readonly array $breadcrumbs = [],
        public readonly ?string $status = null,
    ) {
        if (preg_match('/^[A-Z]{2}-[A-Z0-9]+(?:-[A-Z0-9]+)*$/', $screenId) !== 1) {
            throw new InvalidArgumentException("Invalid screen ID [{$screenId}].");
        }
    }

    public function render(): View
    {
        return view('components.admin.page-header');
    }
}
