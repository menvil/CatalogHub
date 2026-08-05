<?php

declare(strict_types=1);

namespace App\View\Components\SiteAdmin;

use App\Models\Site;
use App\Models\SiteMembership;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

final class SiteSelector extends Component
{
    /** @var Collection<int, Site> */
    public readonly Collection $sites;

    /** @param iterable<Site>|null $sites */
    public function __construct(
        public readonly Site $currentSite,
        public readonly User $user,
        ?iterable $sites = null,
    ) {
        $this->sites = $sites === null
            ? $this->authorizedSites()
            : collect($sites)->values();
    }

    public function render(): View
    {
        return view('components.site-admin.site-selector');
    }

    /** @return Collection<int, Site> */
    private function authorizedSites(): Collection
    {
        return SiteMembership::query()
            ->where('user_id', $this->user->getKey())
            ->where('is_active', true)
            ->whereIn('site_id', Site::query()->administrable()->select('id'))
            ->with(['site.primaryDomain'])
            ->orderBy('site_id')
            ->get()
            ->map(static fn (SiteMembership $membership): Site => $membership->site)
            ->filter(static fn (Site $site): bool => $site->exists)
            ->values();
    }
}
