<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\Imports\CentralBrandExternalIdentity;
use App\Models\Imports\ImportSource;
use App\Support\Imports\ExternalIdentityNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CentralBrandExternalIdentity> */
final class CentralBrandExternalIdentityFactory extends Factory
{
    protected $model = CentralBrandExternalIdentity::class;

    public function definition(): array
    {
        $externalId = 'brand-'.fake()->unique()->numerify('########');

        return [
            'central_brand_id' => CentralBrand::factory(),
            'import_source_id' => ImportSource::factory(),
            'external_id' => $externalId,
            'external_id_hash' => ExternalIdentityNormalizer::hash($externalId),
            'external_url' => 'https://example.test/brands/'.$externalId,
        ];
    }

    public function externalId(string $externalId): self
    {
        $normalized = ExternalIdentityNormalizer::externalId($externalId);

        return $this->state(fn (): array => [
            'external_id' => $normalized,
            'external_id_hash' => ExternalIdentityNormalizer::hash($normalized),
        ]);
    }
}
