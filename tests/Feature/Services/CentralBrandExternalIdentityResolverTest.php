<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\Imports\CentralBrandExternalIdentity;
use App\Models\Imports\ImportSource;
use App\Services\Imports\CentralBrandExternalIdentityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CentralBrandExternalIdentityResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolver_uses_exact_opaque_value_and_source_namespace(): void
    {
        $brand = CentralBrand::factory()->create(['name' => 'Samsung']);
        $source = ImportSource::factory()->create();
        $otherSource = ImportSource::factory()->create();
        CentralBrandExternalIdentity::factory()->for($brand, 'brand')->for($source, 'source')->externalId('000123')->create();
        $resolver = app(CentralBrandExternalIdentityResolver::class);

        self::assertTrue($brand->is($resolver->findBrand($source, '  000123  ')));
        self::assertNull($resolver->findBrand($source, '000124'));
        self::assertNull($resolver->findBrand($otherSource, '000123'));

        CentralBrandExternalIdentity::factory()->for($brand, 'brand')->for($source, 'source')->externalId('ABC')->create();
        self::assertTrue($brand->is($resolver->findBrand($source, 'ABC')));
        self::assertNull($resolver->findBrand($source, 'abc'));
    }
}
