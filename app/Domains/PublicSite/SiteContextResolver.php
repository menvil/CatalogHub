<?php

namespace App\Domains\PublicSite;

use App\Enums\SiteStatus;
use App\Models\Site;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class SiteContextResolver
{
    public function resolveHost(string $host): Site
    {
        $site = Site::query()
            ->where('domain', strtolower($host))
            ->where('status', SiteStatus::Active)
            ->first();

        if (! $site instanceof Site) {
            throw new NotFoundHttpException('Public site not found.');
        }

        return $site;
    }

    public function resolve(string $host, string $locale): Site
    {
        $site = Site::query()
            ->where('domain', strtolower($host))
            ->where('status', SiteStatus::Active)
            ->whereExists(function ($query) use ($locale): void {
                $query->select('site_locales.id')
                    ->from('site_locales')
                    ->whereColumn('site_locales.site_id', 'sites.id')
                    ->where('site_locales.locale_code', $locale)
                    ->where('site_locales.is_enabled', true);
            })
            ->with(['theme.manifest', 'market'])
            ->first();

        if (! $site instanceof Site) {
            throw new NotFoundHttpException('Public site or locale not found.');
        }

        return $site;
    }
}
