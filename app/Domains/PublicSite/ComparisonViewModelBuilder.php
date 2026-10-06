<?php

namespace App\Domains\PublicSite;

use App\Exceptions\Units\CannotConvertUnitException;
use App\Models\MeasurementUnit;
use App\Models\SiteProductProjection;
use App\Services\Units\UnitConverter;
use Illuminate\Support\Collection;

final class ComparisonViewModelBuilder
{
    /** @param Collection<int, SiteProductProjection> $projections
     * @return array<string, mixed>
     */
    public function build(Collection $projections): array
    {
        $projections = $projections->values();
        $products = $projections->map(fn ($p) => ['title' => $p->title, 'slug' => $p->slug, 'media' => $p->media_json ?? []])->all();
        $error = fn ($message) => ['products' => $products, 'sections' => [], 'error' => $message];
        if ($projections->count() < 2 || $projections->count() > 4) {
            return $error('Select two to four available products to compare.');
        }
        $contexts = $projections->map(fn ($p) => [$p->site_id, $p->locale, data_get($p->payload_json, 'category.id')]);
        if ($contexts->first()[2] === null || $contexts->uniqueStrict()->count() !== 1) {
            return $error('Products must belong to the same category, site and locale.');
        }
        foreach ($projections as $p) {
            if (($p->payload_json['attribute_identity_version'] ?? null) !== 2) {
                return $error('Rebuild comparison projections before comparing.');
            }
        }
        $configuration = $projections->first()->payload_json['comparison'] ?? [];
        if ($projections->contains(fn ($p) => ($p->payload_json['comparison'] ?? []) !== $configuration)) {
            return $error('Comparison schema changed. Rebuild projections.');
        }
        $facts = $projections->map(fn ($p) => array_column($p->payload_json['attributes'], null, 'assignment_id'));
        $unitIds = $projections->flatMap(fn ($p) => array_column($p->payload_json['attributes'] ?? [], 'canonical_measurement_unit_id'))->filter()->unique()->all();
        $units = MeasurementUnit::query()->whereIn('id', $unitIds)->get()->keyBy('id');
        $sections = [];
        foreach ($configuration as $row) {
            $assignmentId = $row['category_attribute_assignment_id'];
            $attributes = $facts->map(fn ($values) => $values[$assignmentId] ?? null);
            $reference = $attributes->first(fn ($attribute) => $attribute !== null);
            if ($reference === null || $attributes->contains(fn ($a) => $a !== null && ($a['definition_id'] !== $reference['definition_id'] || $a['data_type'] !== $reference['data_type']))) {
                return $error('Comparison identity changed. Rebuild projections.');
            }
            $key = $reference['section']['id'] ?? 'ungrouped';
            $sections[$key] ??= ['label' => $reference['section']['label'], 'attributes' => []];
            try {
                $equality = $attributes->map(fn ($a) => json_encode($this->identity($a, $units), JSON_THROW_ON_ERROR))->all();
            } catch (CannotConvertUnitException|\UnexpectedValueException $exception) {
                return $error('Comparison measurement context is invalid. Rebuild projections.');
            }
            $sections[$key]['attributes'][] = ['label' => $reference['label'], 'assignment_id' => $assignmentId,
                'values' => $attributes->map(fn ($a) => $a === null || ! $a['has_value'] ? '—' : $a['display_value'])->all(),
                'is_equal' => count(array_unique($equality)) === 1];
        }

        return ['products' => $products, 'sections' => array_values($sections), 'error' => null];
    }

    /** @param array<string, mixed>|null $attribute
     * @param  Collection<int, MeasurementUnit>  $units
     * @return array<string, mixed>
     */
    private function identity(?array $attribute, Collection $units): array
    {
        if ($attribute === null || ! $attribute['has_value'] || $attribute['canonical_value'] === null) {
            return ['missing' => true];
        }
        $value = $attribute['canonical_value'];
        if (in_array($attribute['data_type'], ['integer', 'decimal'], true)) {
            $unit = $attribute['canonical_measurement_unit_id'] === null ? null : $units->get($attribute['canonical_measurement_unit_id']);
            if ($attribute['canonical_measurement_unit_id'] !== null && ($unit === null || $unit->dimension_id !== $attribute['measurement_dimension_id'])) {
                throw new \UnexpectedValueException('Invalid canonical unit identity.');
            }
            if ($unit !== null && filled($attribute['canonical_unit']) && $unit->code !== $attribute['canonical_unit']) {
                $value = app(UnitConverter::class)->convert($value, $attribute['canonical_unit'], $unit);
            }
            $value = $this->number((string) $value);
        } elseif ($attribute['data_type'] === 'multi_enum') {
            $value = array_values(array_unique($value));
            sort($value, SORT_STRING);
        }

        return ['missing' => false, 'type' => $attribute['data_type'], 'dimension_id' => $attribute['measurement_dimension_id'], 'value' => $value];
    }

    private function number(string $value): string
    {
        if (preg_match('/^([+-]?)([0-9]+)(?:\.([0-9]*))?[eE]([+-]?[0-9]+)$/', $value, $matches) === 1) {
            $exponent = (int) $matches[4];
            if (abs($exponent) > 1000) {
                throw new \UnexpectedValueException('Numeric comparison exponent exceeds supported precision.');
            }
            $digits = $matches[2].$matches[3];
            $point = strlen($matches[2]) + $exponent;
            $value = $matches[1].($point <= 0 ? '0.'.str_repeat('0', -$point).$digits
                : ($point >= strlen($digits) ? $digits.str_repeat('0', $point - strlen($digits))
                : substr($digits, 0, $point).'.'.substr($digits, $point)));
        }
        $parts = explode('.', $value, 2);
        $whole = ltrim(ltrim($parts[0], '+-'), '0');
        $fraction = rtrim($parts[1] ?? '', '0');
        $number = ($whole === '' ? '0' : $whole).($fraction === '' ? '' : '.'.$fraction);

        return str_starts_with($value, '-') && $number !== '0' ? '-'.$number : $number;
    }
}
