<?php

namespace App\Support\Facets;

use App\Data\Facets\AppliedFacetFilter;

final class FacetUrlBuilder
{
    /** @param array<string, mixed> $query */
    public function removeAppliedFilter(
        string $baseUrl,
        array $query,
        AppliedFacetFilter $filter,
    ): string {
        unset($query['page']);

        if (count($filter->queryKeys) === 1 && ! is_array($filter->value)) {
            $key = $filter->queryKeys[0];
            $values = $this->listValues($query[$key] ?? null);
            $values = array_values(array_filter(
                $values,
                fn (string $value): bool => $value !== (string) $filter->value,
            ));

            if ($values === []) {
                unset($query[$key]);
            } else {
                $query[$key] = implode(',', $values);
            }
        } else {
            foreach ($filter->queryKeys as $key) {
                unset($query[$key]);
            }
        }

        return $this->toUrl($baseUrl, $query);
    }

    public function clearAll(string $baseUrl): string
    {
        return strtok($baseUrl, '?') ?: $baseUrl;
    }

    /** @param array<string, mixed> $query */
    public function toUrl(string $baseUrl, array $query): string
    {
        $baseUrl = $this->clearAll($baseUrl);
        $query = array_filter($query, fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
        ksort($query, SORT_STRING);
        $parts = [];

        foreach ($query as $key => $value) {
            $value = is_array($value) ? implode(',', $this->listValues($value)) : (string) $value;
            $parts[] = rawurlencode((string) $key).'='.str_replace('%2C', ',', rawurlencode($value));
        }

        return $parts === [] ? $baseUrl : $baseUrl.'?'.implode('&', $parts);
    }

    /** @return list<string> */
    private function listValues(mixed $value): array
    {
        $values = is_array($value) ? $value : explode(',', (string) $value);

        return array_values(array_filter(array_map(
            fn (mixed $item): string => trim((string) $item),
            $values,
        )));
    }
}
