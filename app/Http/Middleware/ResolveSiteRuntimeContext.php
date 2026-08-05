<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\PublicSite\LocalizedUrlResolver;
use App\Exceptions\Sites\InvalidSiteRuntimeConfigurationException;
use App\Models\Site;
use App\Models\SiteDomain;
use App\Services\Sites\SiteContextValueResolver;
use App\Services\Sites\SiteResolver;
use App\Support\PublicSite\PublicErrorContext;
use App\Support\Sites\SiteRuntimeContext;
use App\Support\Themes\PublicThemeContext;
use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class ResolveSiteRuntimeContext
{
    public function __construct(
        private Application $app,
        private SiteResolver $sites,
        private SiteContextValueResolver $values,
        private LocalizedUrlResolver $urls,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->app->forgetInstance(SiteRuntimeContext::class);
        $this->app->forgetInstance(PublicThemeContext::class);
        [$site, $domain] = $this->resolveSiteAndDomain($request);
        $requestedLocale = $request->route('locale');
        $context = $this->values->resolve(
            $site,
            $domain,
            is_string($requestedLocale) ? $requestedLocale : null,
        );
        $request->attributes->set(SiteRuntimeContext::class, $context);
        $request->attributes->set(PublicErrorContext::class, new PublicErrorContext(
            siteName: $site->name,
            homeUrl: $this->urls->home($site, $context->resolvedLocale),
        ));

        $previousLocale = $this->app->getLocale();
        $previousTimezone = date_default_timezone_get();
        $this->app->setLocale($context->resolvedLocale);
        date_default_timezone_set($context->timezone);

        try {
            return $next($request);
        } finally {
            $this->app->setLocale($previousLocale);
            date_default_timezone_set($previousTimezone);
            $this->app->forgetInstance(SiteRuntimeContext::class);
            $this->app->forgetInstance(PublicThemeContext::class);
            $request->attributes->remove(SiteRuntimeContext::class);
        }
    }

    /** @return array{Site, SiteDomain} */
    private function resolveSiteAndDomain(Request $request): array
    {
        $administrativeSite = $request->attributes->get('site_context');

        if (! $administrativeSite instanceof Site) {
            $domain = $this->sites->resolveDomain($request->getHost());

            return [$domain->site, $domain];
        }

        abort_unless($administrativeSite->status->allowsAdministration(), 404);
        $administrativeSite->loadMissing(['market', 'locales.locale', 'domains']);
        $domain = $administrativeSite->domains->first(
            static fn (SiteDomain $candidate): bool => $candidate->is_primary && $candidate->is_active,
        );

        if (! $domain instanceof SiteDomain) {
            throw InvalidSiteRuntimeConfigurationException::forSite(
                (string) $administrativeSite->code,
                'an active primary domain is required',
            );
        }

        return [$administrativeSite, $domain];
    }
}
