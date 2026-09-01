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
        $exactId = $this->exactId($query);
        $normalizedQuery = OrganizationNameNormalizer::search($query ?? '');
        $normalizedPrefix = OrganizationNameNormalizer::prefixForNormalizedName($normalizedQuery);

        /** @var Collection<int, Organization> $organizations */
        $organizations = Organization::query()
            ->when($exactId !== null, static fn ($builder) => $builder->whereKey($exactId))
            ->when(
                $exactId === null && $normalizedQuery !== '',
                static function ($builder) use ($normalizedPrefix, $normalizedQuery): void {
                    if ($normalizedPrefix === $normalizedQuery) {
                        $builder->whereRaw(
                            "normalized_name_prefix LIKE ? ESCAPE '!'",
                            [LiteralLikePattern::startingWith($normalizedQuery)],
                        );

                        return;
                    }

                    $builder
                        ->where('normalized_name_prefix', $normalizedPrefix)
                        ->whereRaw(
                            "normalized_name LIKE ? ESCAPE '!'",
                            [LiteralLikePattern::startingWith($normalizedQuery)],
                        );
                },
            )
            ->orderBy('normalized_name_prefix')
            ->orderBy('normalized_name')
            ->orderBy('id')
            ->limit(self::LIMIT)
            ->get(['id', 'name', 'normalized_name', 'normalized_name_prefix']);

        return $organizations
            ->map(fn (Organization $organization): array => $this->option($organization))
            ->all();
    }

    /** @return array{value: string, label: string, search: string} */
    public function option(Organization $organization): array
    {
        return [
            'value' => (string) $organization->getKey(),
            'label' => sprintf('%s — Organization #%s', $organization->name, $organization->getKey()),
            'search' => sprintf('%s #%s', $organization->normalized_name, $organization->getKey()),
        ];
    }

    private function exactId(?string $query): ?int
    {
        $matches = [];
        if (preg_match('/\A#([1-9][0-9]*)\z/D', trim($query ?? ''), $matches) !== 1) {
            return null;
        }

        $id = filter_var($matches[1], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return is_int($id) ? $id : null;
    }
}
