<?php

declare(strict_types=1);

namespace App\View\Components\SiteAdmin;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use InvalidArgumentException;

final class SyncStatus extends Component
{
    /** @var array<string, array{label: string, description: string, tone: string}> */
    private const STATES = [
        'not-configured' => [
            'label' => 'Not configured',
            'description' => 'Synchronization has not been configured for this site.',
            'tone' => 'neutral',
        ],
        'unknown' => [
            'label' => 'Unknown',
            'description' => 'Synchronization status is currently unavailable.',
            'tone' => 'warning',
        ],
    ];

    /** @var array{label: string, description: string, tone: string} */
    public readonly array $status;

    public function __construct(public readonly string $state = 'not-configured')
    {
        $this->status = self::STATES[$state]
            ?? throw new InvalidArgumentException("Unsupported sync status [{$state}].");
    }

    public function render(): View
    {
        return view('components.site-admin.sync-status');
    }
}
