<?php

declare(strict_types=1);

namespace App\Services\PublicSite;

use App\Domains\PublicSite\LocalizedUrlResolver;
use App\Models\Site;
use App\Models\SiteLocale;
use LogicException;

final readonly class PublicLocaleNavigation
{
    public function __construct(private LocalizedUrlResolver $urls) {}

    /** @return list<array{code: string, label: string, url: string, current: bool}> */
    public function forHome(Site $site, string $currentLocale): array
    {
        if (! $site->relationLoaded('locales')) {
            throw new LogicException('Site locales must be loaded before public locale navigation is built.');
        }

        if ($site->locales->contains(
            static fn (SiteLocale $locale): bool => ! $locale->relationLoaded('locale'),
        )) {
            throw new LogicException('Site locale catalog relations must be loaded before public locale navigation is built.');
        }

        return $site->locales
            ->filter(static fn (SiteLocale $locale): bool => $locale->is_enabled
                && ($locale->locale === null || $locale->locale->is_active))
            ->sortBy([['position', 'asc'], ['id', 'asc']])
            ->map(fn (SiteLocale $locale): array => [
                'code' => $locale->locale_code,
                'label' => $locale->locale?->native_name ?: $locale->locale_code,
                'url' => $this->urls->home($site, $locale->locale_code),
                'current' => $locale->locale_code === $currentLocale,
            ])
            ->values()
            ->all();
    }
}
