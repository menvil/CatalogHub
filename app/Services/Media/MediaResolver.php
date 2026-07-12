<?php

namespace App\Services\Media;

use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\ValueObjects\Media\MediaResolutionResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class MediaResolver
{
    public function __construct(private readonly MediaUrlGenerator $urlGenerator) {}

    public function resolve(
        string $entityType,
        int $entityId,
        string $role,
        ?string $locale = null,
        ?int $siteId = null,
        ?int $marketId = null,
    ): ?MediaAsset {
        return $this->resolveAssignment($entityType, $entityId, $role, $locale, $siteId, $marketId)?->asset;
    }

    public function resolveAssignment(
        string $entityType,
        int $entityId,
        string $role,
        ?string $locale = null,
        ?int $siteId = null,
        ?int $marketId = null,
    ): ?MediaAssignment {
        return $this->matchAssignment(
            $this->assignmentsForCandidates($entityType, $entityId, $role, $locale, $siteId, $marketId),
            $this->candidates($role, $locale, $siteId, $marketId),
        );
    }

    public function explain(
        string $entityType,
        int $entityId,
        string $role,
        ?string $locale = null,
        ?int $siteId = null,
        ?int $marketId = null,
    ): MediaResolutionResult {
        $fallbackChain = [];

        $candidates = $this->candidates($role, $locale, $siteId, $marketId);
        $assignments = $this->assignmentsForCandidates($entityType, $entityId, $role, $locale, $siteId, $marketId);

        foreach ($candidates as $candidate) {
            $label = $this->candidateLabel($candidate);
            $fallbackChain[] = $label;

            $assignment = $this->matchAssignment($assignments, [$candidate]);

            if ($assignment instanceof MediaAssignment) {
                return new MediaResolutionResult(
                    asset: $assignment->asset,
                    assignment: $assignment,
                    fallbackChain: $fallbackChain,
                    matchedStep: $label,
                    placeholderUrl: $this->urlGenerator->placeholder($role),
                );
            }
        }

        $fallbackChain[] = 'placeholder';

        return new MediaResolutionResult(
            asset: null,
            assignment: null,
            fallbackChain: $fallbackChain,
            matchedStep: 'placeholder',
            placeholderUrl: $this->urlGenerator->placeholder($role),
        );
    }

    /**
     * @return Collection<int, MediaAssignment>
     */
    private function assignmentsForCandidates(
        string $entityType,
        int $entityId,
        string $role,
        ?string $locale,
        ?int $siteId,
        ?int $marketId,
    ): Collection {
        $roles = array_values(array_unique(array_merge([$role], $this->fallbackRoles($role))));
        $locales = array_values(array_unique(array_filter([$locale], fn (?string $value): bool => $value !== null)));
        $siteIds = array_values(array_unique(array_filter([$siteId], fn (?int $value): bool => $value !== null)));
        $marketIds = array_values(array_unique(array_filter([$marketId], fn (?int $value): bool => $value !== null)));

        return MediaAssignment::query()
            ->with('asset')
            ->forEntity($entityType, $entityId)
            ->whereIn('role', $roles)
            ->where(function (Builder $query) use ($locales): void {
                $query->whereNull('locale');

                if ($locales !== []) {
                    $query->orWhereIn('locale', $locales);
                }
            })
            ->where(function (Builder $query) use ($siteIds): void {
                $query->whereNull('site_id');

                if ($siteIds !== []) {
                    $query->orWhereIn('site_id', $siteIds);
                }
            })
            ->where(function (Builder $query) use ($marketIds): void {
                $query->whereNull('market_id');

                if ($marketIds !== []) {
                    $query->orWhereIn('market_id', $marketIds);
                }
            })
            ->orderByDesc('is_primary')
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, MediaAssignment>  $assignments
     * @param  list<array{role: string, locale: ?string, site_id: ?int, market_id: ?int}>  $candidates
     */
    private function matchAssignment(Collection $assignments, array $candidates): ?MediaAssignment
    {
        foreach ($candidates as $candidate) {
            $assignment = $assignments->first(fn (MediaAssignment $assignment): bool => $assignment->role === $candidate['role']
                && $assignment->locale === $candidate['locale']
                && $assignment->site_id === $candidate['site_id']
                && $assignment->market_id === $candidate['market_id']);

            if ($assignment instanceof MediaAssignment) {
                return $assignment;
            }
        }

        return null;
    }

    /**
     * @return list<array{role: string, locale: ?string, site_id: ?int, market_id: ?int}>
     */
    private function candidates(string $role, ?string $locale, ?int $siteId, ?int $marketId): array
    {
        $candidates = [];
        $roles = array_values(array_unique(array_merge([$role], $this->fallbackRoles($role))));

        foreach ($roles as $candidateRole) {
            if ($siteId !== null && $marketId !== null && $locale !== null) {
                $candidates[] = ['role' => $candidateRole, 'locale' => $locale, 'site_id' => $siteId, 'market_id' => $marketId];
            }

            if ($siteId !== null && $marketId !== null) {
                $candidates[] = ['role' => $candidateRole, 'locale' => null, 'site_id' => $siteId, 'market_id' => $marketId];
            }

            if ($siteId !== null && $locale !== null) {
                $candidates[] = ['role' => $candidateRole, 'locale' => $locale, 'site_id' => $siteId, 'market_id' => null];
            }

            if ($marketId !== null && $locale !== null) {
                $candidates[] = ['role' => $candidateRole, 'locale' => $locale, 'site_id' => null, 'market_id' => $marketId];
            }

            if ($locale !== null) {
                $candidates[] = ['role' => $candidateRole, 'locale' => $locale, 'site_id' => null, 'market_id' => null];
            }

            if ($siteId !== null) {
                $candidates[] = ['role' => $candidateRole, 'locale' => null, 'site_id' => $siteId, 'market_id' => null];
            }

            if ($marketId !== null) {
                $candidates[] = ['role' => $candidateRole, 'locale' => null, 'site_id' => null, 'market_id' => $marketId];
            }

            $candidates[] = ['role' => $candidateRole, 'locale' => null, 'site_id' => null, 'market_id' => null];
        }

        return collect($candidates)
            ->unique(fn (array $candidate): string => implode('|', [
                $candidate['role'],
                $candidate['locale'] ?? 'null',
                $candidate['site_id'] ?? 'null',
                $candidate['market_id'] ?? 'null',
            ]))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function fallbackRoles(string $role): array
    {
        return match ($role) {
            'card' => ['main', 'gallery'],
            'og' => ['hero', 'main', 'gallery'],
            'hero' => ['main', 'gallery'],
            'main' => ['gallery'],
            default => ['main', 'gallery'],
        };
    }

    /**
     * @param  array{role: string, locale: ?string, site_id: ?int, market_id: ?int}  $candidate
     */
    private function candidateLabel(array $candidate): string
    {
        $parts = array_filter([
            $candidate['site_id'] === null ? null : 'site',
            $candidate['market_id'] === null ? null : 'market',
            $candidate['locale'] === null ? null : 'locale',
            $candidate['role'],
        ]);

        return implode(' + ', $parts) ?: 'global';
    }
}
