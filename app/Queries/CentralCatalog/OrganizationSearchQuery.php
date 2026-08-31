<?php

declare(strict_types=1);

namespace App\Queries\CentralCatalog;

use App\Contracts\Persistence\RawSqlPersistenceBoundary;
use App\Models\Organization;
use App\Support\Database\LiteralLikePattern;
use App\Support\Normalization\OrganizationNameNormalizer;
use Illuminate\Support\Collection;

final class OrganizationSearchQuery implements RawSqlPersistenceBoundary
{
    public const LIMIT = 20;

    /** @return list<array{value: string, label: string, search: string}> */
    public function search(?string $query): array
    {
        $normalizedQuery = OrganizationNameNormalizer::search($query ?? '');

        /** @var Collection<int, Organization> $organizations */
        $organizations = Organization::query()
            ->when(
                $normalizedQuery !== '',
                static fn ($builder) => $builder->whereRaw(
                    "normalized_name LIKE ? ESCAPE '!'",
                    [LiteralLikePattern::startingWith($normalizedQuery)],
                ),
            )
            ->orderBy('normalized_name')
            ->orderBy('id')
            ->limit(self::LIMIT)
            ->get(['id', 'name', 'normalized_name']);

        return $organizations
            ->map(fn (Organization $organization): array => $this->option($organization))
            ->all();
    }

    /** @return array{value: string, label: string, search: string} */
    public function option(Organization $organization): array
    {
        return [
            'value' => (string) $organization->getKey(),
            'label' => (string) $organization->name,
            'search' => (string) $organization->normalized_name,
        ];
    }
}
