<?php

namespace App\Services\Media;

use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\ValueObjects\Media\MediaResolutionResult;
use Illuminate\Database\Eloquent\Builder;

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
        foreach ($this->candidates($role, $locale, $siteId, $marketId) as $candidate) {
            $assignment = $this->query($entityType, $entityId, $candidate)->first();

            if ($assignment instanceof MediaAssignment) {
                return $assignment;
            }
        }

        return null;
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

        foreach ($this->candidates($role, $locale, $siteId, $marketId) as $candidate) {
            $label = $this->candidateLabel($candidate);
            $fallbackChain[] = $label;

            $assignment = $this->query($entityType, $entityId, $candidate)->first();

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
     * @param array{role: string, locale: ?string, site_id: ?int, market_id: ?int} $candidate
     * @return Builder<MediaAssignment>
     */
    private function query(string $entityType, int $entityId, array $candidate): Builder
    {
        return MediaAssignment::query()
            ->with('asset')
            ->forEntity($entityType, $entityId)
            ->forRole($candidate['role'])
            ->when($candidate['locale'] === null, fn (Builder $query): Builder => $query->whereNull('locale'), fn (Builder $query): Builder => $query->where('locale', $candidate['locale']))
            ->when($candidate['site_id'] === null, fn (Builder $query): Builder => $query->whereNull('site_id'), fn (Builder $query): Builder => $query->where('site_id', $candidate['site_id']))
            ->when($candidate['market_id'] === null, fn (Builder $query): Builder => $query->whereNull('market_id'), fn (Builder $query): Builder => $query->where('market_id', $candidate['market_id']))
            ->orderByDesc('is_primary')
            ->orderBy('position')
            ->orderBy('id');
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
     * @param array{role: string, locale: ?string, site_id: ?int, market_id: ?int} $candidate
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
