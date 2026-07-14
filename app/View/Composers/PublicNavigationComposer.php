<?php

namespace App\View\Composers;

use App\Domains\PublicSite\LocalizedUrlResolver;
use App\Models\Site;
use Illuminate\View\View;

final readonly class PublicNavigationComposer
{
    public function __construct(private LocalizedUrlResolver $urls) {}

    public function compose(View $view): void
    {
        $data = $view->getData();
        $site = $data['site'] ?? null;
        $locale = $data['locale'] ?? null;

        if (! $site instanceof Site || ! is_string($locale) || $locale === '') {
            return;
        }

        $view->with('publicNavigation', [
            'home' => $this->urls->home($site, $locale),
            'search' => $this->urls->search($site, $locale),
        ]);
    }
}
