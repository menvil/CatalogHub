<?php

namespace App\Jobs\Projections;

use App\Actions\Sync\RebuildSiteProductProjectionAction;
use App\Models\SiteProduct;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class RebuildProductProjectionJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 300;

    public function __construct(
        public int $siteProductId,
        public int $triggeredByUserId,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->siteProductId;
    }

    public function handle(?RebuildSiteProductProjectionAction $rebuild = null): void
    {
        $siteProduct = SiteProduct::query()->find($this->siteProductId);
        $admin = User::query()->find($this->triggeredByUserId);

        if (! $siteProduct instanceof SiteProduct || ! $admin instanceof User) {
            return;
        }

        ($rebuild ?? app(RebuildSiteProductProjectionAction::class))->handle(
            $admin,
            $siteProduct,
            triggeredBy: 'correction',
        );
    }
}
