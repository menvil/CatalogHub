<?php

declare(strict_types=1);

namespace Tests\Feature\ReferenceData;

use App\Models\Geography\Country;
use App\Services\Geography\CountryNameResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CountryReference;
use Tests\TestCase;

final class CountryDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_relations_active_scope_and_locale_fallbacks(): void
    {
        $country = CountryReference::get('DE')->load('translations');
        $resolver = app(CountryNameResolver::class);
        $country->translations()->create(['locale' => 'de-DE', 'name' => 'Deutschland (Deutschland)']);
        $country->load('translations');

        $this->assertCount(3, $country->translations);
        $this->assertSame('Deutschland (Deutschland)', $resolver->nameFor($country, 'de-DE'));
        $this->assertSame('Deutschland', $resolver->nameFor($country, 'de-AT'));
        $this->assertSame('Germany', $resolver->nameFor($country, 'fr-FR'));
        $this->assertTrue(Country::query()->active()->whereKey($country->id)->exists());

        $country->update(['is_active' => false]);
        $this->assertFalse(Country::query()->active()->whereKey($country->id)->exists());
    }
}
