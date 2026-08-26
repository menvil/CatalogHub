<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\CentralCatalog\CreateCentralBrandAction;
use App\Actions\CentralCatalog\UpdateCentralBrandAction;
use App\Data\CentralCatalog\CentralBrandInput;
use App\Enums\AuditAction;
use App\Enums\CentralBrandStatus;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\CountryReference;
use Tests\TestCase;

final class CentralBrandProfileActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_persists_normalized_rich_profile_and_minimized_audit_snapshot(): void
    {
        $brand = app(CreateCentralBrandAction::class)->handle(User::factory()->create(), new CentralBrandInput(
            name: ' Samsung ',
            slug: 'samsung',
            hasWebsiteUrl: true,
            websiteUrl: ' https://www.samsung.com/ ',
            hasCountryId: true,
            countryId: CountryReference::id('KR'),
            hasFoundedYear: true,
            foundedYear: 1938,
            hasSupportUrl: true,
            supportUrl: ' https://www.samsung.com/support/ ',
            hasContactEmail: true,
            contactEmail: ' support@example.com ',
            hasPrimaryColor: true,
            primaryColor: '#1428a0',
        ));

        $this->assertSame(CentralBrandStatus::Draft, $brand->status);
        $this->assertSame(1938, $brand->founded_year);
        $this->assertSame('https://www.samsung.com/support/', $brand->support_url);
        $this->assertSame('support@example.com', $brand->contact_email);
        $this->assertSame('#1428A0', $brand->primary_color);

        $entry = AuditLogEntry::query()->sole();
        $this->assertSame(AuditAction::CatalogBrandCreated->value, $entry->action);
        $this->assertSame([
            'name' => 'Samsung',
            'slug' => 'samsung',
            'status' => 'draft',
            'website_url' => 'https://www.samsung.com/',
            'country_code' => 'KR',
            'founded_year' => 1938,
            'support_url' => 'https://www.samsung.com/support/',
            'contact_email' => 'support@example.com',
            'primary_color' => '#1428A0',
        ], $entry->after_json);
        $this->assertStringNotContainsString('country_id', json_encode($entry->after_json, JSON_THROW_ON_ERROR));
    }

    public function test_minimal_create_keeps_every_optional_profile_value_null(): void
    {
        $brand = app(CreateCentralBrandAction::class)->handle(
            User::factory()->create(),
            new CentralBrandInput(name: 'Minimal Brand'),
        );

        foreach (['website_url', 'country_id', 'founded_year', 'support_url', 'contact_email', 'primary_color'] as $field) {
            $this->assertNull($brand->getAttribute($field));
        }
    }

    public function test_update_changes_all_profile_fields_once_and_omitted_fields_are_retained(): void
    {
        $brand = CentralBrand::factory()->create([
            'name' => 'Samsung',
            'slug' => 'samsung',
            'founded_year' => 1938,
            'support_url' => 'https://old.example/support',
            'contact_email' => 'old@example.com',
            'primary_color' => '#000000',
        ]);

        $updated = app(UpdateCentralBrandAction::class)->handle(User::factory()->create(), $brand, new CentralBrandInput(
            name: 'Samsung',
            slug: 'samsung',
            hasFoundedYear: true,
            foundedYear: 1969,
            hasSupportUrl: true,
            supportUrl: 'https://new.example/support',
            hasContactEmail: true,
            contactEmail: 'new@example.com',
            hasPrimaryColor: true,
            primaryColor: '#1428a0',
        ));

        $this->assertSame([1969, 'https://new.example/support', 'new@example.com', '#1428A0'], [
            $updated->founded_year,
            $updated->support_url,
            $updated->contact_email,
            $updated->primary_color,
        ]);
        $entry = AuditLogEntry::query()->sole();
        $this->assertSame([
            'founded_year' => 1938,
            'support_url' => 'https://old.example/support',
            'contact_email' => 'old@example.com',
            'primary_color' => '#000000',
        ], $entry->before_json);
        $this->assertSame([
            'founded_year' => 1969,
            'support_url' => 'https://new.example/support',
            'contact_email' => 'new@example.com',
            'primary_color' => '#1428A0',
        ], $entry->after_json);

        $retained = app(UpdateCentralBrandAction::class)->handle(User::factory()->create(), $updated, new CentralBrandInput(
            name: 'Samsung Electronics',
            slug: 'samsung',
        ));
        $this->assertSame([1969, 'https://new.example/support', 'new@example.com', '#1428A0'], [
            $retained->founded_year,
            $retained->support_url,
            $retained->contact_email,
            $retained->primary_color,
        ]);
    }

    public function test_explicit_blank_or_null_clears_profile_and_audits_old_to_null(): void
    {
        $brand = CentralBrand::factory()->create([
            'name' => 'Samsung',
            'slug' => 'samsung',
            'founded_year' => 1938,
            'support_url' => 'https://example.com/support',
            'contact_email' => 'support@example.com',
            'primary_color' => '#1428A0',
        ]);

        $updated = app(UpdateCentralBrandAction::class)->handle(User::factory()->create(), $brand, new CentralBrandInput(
            name: 'Samsung',
            slug: 'samsung',
            hasFoundedYear: true,
            foundedYear: null,
            hasSupportUrl: true,
            supportUrl: '   ',
            hasContactEmail: true,
            contactEmail: null,
            hasPrimaryColor: true,
            primaryColor: '',
        ));

        foreach (['founded_year', 'support_url', 'contact_email', 'primary_color'] as $field) {
            $this->assertNull($updated->getAttribute($field));
        }
        $entry = AuditLogEntry::query()->sole();
        $this->assertSame([
            'founded_year' => null,
            'support_url' => null,
            'contact_email' => null,
            'primary_color' => null,
        ], $entry->after_json);
    }

    public function test_canonical_equivalent_profile_update_is_a_no_op(): void
    {
        $brand = CentralBrand::factory()->create([
            'name' => 'Samsung',
            'slug' => 'samsung',
            'support_url' => 'https://example.com/support',
            'contact_email' => 'support@example.com',
            'primary_color' => '#1428A0',
        ]);

        app(UpdateCentralBrandAction::class)->handle(User::factory()->create(), $brand, new CentralBrandInput(
            name: 'Samsung',
            slug: 'samsung',
            hasSupportUrl: true,
            supportUrl: ' https://example.com/support ',
            hasContactEmail: true,
            contactEmail: ' support@example.com ',
            hasPrimaryColor: true,
            primaryColor: '#1428a0',
        ));

        $this->assertDatabaseCount('audit_log_entries', 0);
    }

    public function test_founded_year_accepts_historical_and_current_year_and_rejects_out_of_range_values(): void
    {
        foreach ([1938, (int) now()->year] as $index => $year) {
            $brand = app(CreateCentralBrandAction::class)->handle(User::factory()->create(), new CentralBrandInput(
                name: "Valid Founded {$index}",
                hasFoundedYear: true,
                foundedYear: $year,
            ));
            $this->assertSame($year, $brand->founded_year);
        }

        foreach ([999, (int) now()->year + 1] as $year) {
            $this->assertInvalidProfile('founded_year', new CentralBrandInput(
                name: "Invalid Founded {$year}",
                hasFoundedYear: true,
                foundedYear: $year,
            ));
        }
    }

    public function test_support_url_email_and_color_domain_validation_rejects_unsafe_values_without_mutation_or_audit(): void
    {
        foreach ([
            ['support_url', new CentralBrandInput(name: 'Bad Javascript', hasSupportUrl: true, supportUrl: 'javascript:alert(1)')],
            ['support_url', new CentralBrandInput(name: 'Bad Data', hasSupportUrl: true, supportUrl: 'data:text/plain,test')],
            ['support_url', new CentralBrandInput(name: 'Bad Relative', hasSupportUrl: true, supportUrl: '/support')],
            ['support_url', new CentralBrandInput(name: 'Bad Url', hasSupportUrl: true, supportUrl: 'not-a-url')],
            ['contact_email', new CentralBrandInput(name: 'Bad Email', hasContactEmail: true, contactEmail: 'not-an-email')],
            ['primary_color', new CentralBrandInput(name: 'No Hash', hasPrimaryColor: true, primaryColor: '1428A0')],
            ['primary_color', new CentralBrandInput(name: 'Short Color', hasPrimaryColor: true, primaryColor: '#123')],
            ['primary_color', new CentralBrandInput(name: 'Bad Hex', hasPrimaryColor: true, primaryColor: '#GGGGGG')],
            ['primary_color', new CentralBrandInput(name: 'Rgb Color', hasPrimaryColor: true, primaryColor: 'rgb(1,2,3)')],
            ['primary_color', new CentralBrandInput(name: 'Named Color', hasPrimaryColor: true, primaryColor: 'red')],
        ] as [$field, $input]) {
            $this->assertInvalidProfile($field, $input);
        }
    }

    private function assertInvalidProfile(string $field, CentralBrandInput $input): void
    {
        $beforeBrands = CentralBrand::query()->count();
        $beforeAudits = AuditLogEntry::query()->count();

        try {
            app(CreateCentralBrandAction::class)->handle(User::factory()->create(), $input);
            $this->fail("Expected {$field} validation failure.");
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }

        $this->assertSame($beforeBrands, CentralBrand::query()->count());
        $this->assertSame($beforeAudits, AuditLogEntry::query()->count());
    }
}
