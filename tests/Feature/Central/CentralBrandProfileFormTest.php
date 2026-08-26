<?php

declare(strict_types=1);

namespace Tests\Feature\Central;

use App\Enums\CentralBrandStatus;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CountryReference;
use Tests\TestCase;

final class CentralBrandProfileFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_http_create_accepts_rich_profile_casts_year_and_ignores_out_of_contract_fields(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('central.brands.store'), [
                'name' => 'Samsung',
                'slug' => 'samsung',
                'website_url' => 'https://www.samsung.com/',
                'country_id' => (string) CountryReference::id('KR'),
                'founded_year' => '1938',
                'support_url' => ' https://www.samsung.com/support/ ',
                'contact_email' => ' support@example.com ',
                'primary_color' => '#1428a0',
                'status' => CentralBrandStatus::Active->value,
                'normalized_name' => 'attacker',
                'normalized_name_hash' => str_repeat('0', 64),
                'parent_company' => 'Samsung Group',
                'products_count' => 99,
                'quality_score' => 100,
            ])
            ->assertRedirect(route('central.brands.index'))
            ->assertSessionHas('success', 'Brand created.');

        $brand = CentralBrand::query()->sole();
        $this->assertSame(1938, $brand->founded_year);
        $this->assertSame('https://www.samsung.com/support/', $brand->support_url);
        $this->assertSame('support@example.com', $brand->contact_email);
        $this->assertSame('#1428A0', $brand->primary_color);
        $this->assertSame(CentralBrandStatus::Draft, $brand->status);
        $this->assertSame('samsung', $brand->normalized_name);
    }

    public function test_edit_renders_all_profile_values_read_only_status_logo_and_media_link(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/originals/logo.png', 'logo');
        $brand = CentralBrand::factory()->archived()->create([
            'name' => 'Samsung',
            'slug' => 'samsung',
            'website_url' => 'https://www.samsung.com/',
            'country_id' => CountryReference::id('KR'),
            'founded_year' => 1938,
            'support_url' => 'https://www.samsung.com/support/',
            'contact_email' => 'support@example.com',
            'primary_color' => '#1428A0',
        ]);
        $asset = MediaAsset::factory()->create(['disk' => 'public', 'original_path' => 'media/originals/logo.png']);
        MediaAssignment::factory()->for($asset, 'asset')->create([
            'entity_type' => MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND,
            'entity_id' => $brand->id,
            'role' => MediaAssignment::ROLE_BRAND_LOGO,
            'is_primary' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('central.brands.edit', $brand))
            ->assertOk()
            ->assertSee('value="1938"', false)
            ->assertSee('value="https://www.samsung.com/support/"', false)
            ->assertSee('value="support@example.com"', false)
            ->assertSee('value="#1428A0"', false)
            ->assertSee('Samsung logo')
            ->assertSee('Manage Media')
            ->assertSee('href="'.route('central.brands.media', $brand, absolute: false).'"', false)
            ->assertSee('Archived')
            ->assertDontSee('name="status"', false)
            ->assertDontSee('Upload logo');
    }

    public function test_http_update_omits_then_explicitly_clears_all_new_nullable_fields(): void
    {
        $brand = CentralBrand::factory()->create([
            'name' => 'Samsung',
            'slug' => 'samsung',
            'founded_year' => 1938,
            'support_url' => 'https://example.com/support',
            'contact_email' => 'support@example.com',
            'primary_color' => '#1428A0',
        ]);
        $this->actingAs(User::factory()->create());

        $this->patch(route('central.brands.update', $brand), [
            'name' => 'Samsung Electronics',
            'slug' => 'samsung',
        ])->assertRedirect(route('central.brands.edit', $brand));

        $retained = $brand->fresh();
        $this->assertSame([1938, 'https://example.com/support', 'support@example.com', '#1428A0'], [
            $retained->founded_year,
            $retained->support_url,
            $retained->contact_email,
            $retained->primary_color,
        ]);

        $this->patch(route('central.brands.update', $brand), [
            'name' => 'Samsung Electronics',
            'slug' => 'samsung',
            'founded_year' => '',
            'support_url' => '',
            'contact_email' => '',
            'primary_color' => '',
        ])->assertRedirect(route('central.brands.edit', $brand));

        $cleared = $brand->fresh();
        foreach (['founded_year', 'support_url', 'contact_email', 'primary_color'] as $field) {
            $this->assertNull($cleared->getAttribute($field));
        }
    }

    public function test_invalid_profile_submission_preserves_old_input_and_mutates_nothing(): void
    {
        $brand = CentralBrand::factory()->create([
            'name' => 'Samsung',
            'slug' => 'samsung',
            'founded_year' => 1938,
            'support_url' => 'https://example.com/support',
            'contact_email' => 'support@example.com',
            'primary_color' => '#1428A0',
        ]);
        $before = $brand->getAttributes();
        $url = route('central.brands.edit', $brand);

        $this->actingAs(User::factory()->create())
            ->from($url)
            ->patch(route('central.brands.update', $brand), [
                'name' => 'Submitted Name',
                'slug' => 'samsung',
                'country_id' => CountryReference::id('JP'),
                'founded_year' => (int) now()->year + 1,
                'support_url' => 'javascript:alert(1)',
                'contact_email' => 'not-an-email',
                'primary_color' => '#GGGGGG',
            ])
            ->assertRedirect($url)
            ->assertSessionHasErrors(['founded_year', 'contact_email', 'primary_color'])
            ->assertSessionHasInput('name', 'Submitted Name')
            ->assertSessionHasInput('support_url', 'javascript:alert(1)')
            ->assertSessionHasInput('contact_email', 'not-an-email')
            ->assertSessionHasInput('primary_color', '#GGGGGG');

        $this->get($url)
            ->assertOk()
            ->assertSee('value="Submitted Name"', false)
            ->assertSee('value="javascript:alert(1)"', false)
            ->assertSee('value="not-an-email"', false)
            ->assertSee('value="#GGGGGG"', false);

        $this->assertEquals($before, $brand->fresh()->getAttributes());
        $this->assertDatabaseCount('audit_log_entries', 0);

        $this->from($url)
            ->patch(route('central.brands.update', $brand), [
                'name' => 'Submitted Name',
                'slug' => 'samsung',
                'support_url' => 'javascript:alert(1)',
            ])
            ->assertRedirect($url)
            ->assertSessionHasErrors('support_url');

        $this->assertEquals($before, $brand->fresh()->getAttributes());
        $this->assertDatabaseCount('audit_log_entries', 0);
    }

    public function test_current_year_http_value_and_http_support_url_are_accepted(): void
    {
        $brand = CentralBrand::factory()->create(['name' => 'Current Year Brand', 'slug' => 'current-year-brand']);

        $this->actingAs(User::factory()->create())
            ->patch(route('central.brands.update', $brand), [
                'name' => 'Current Year Brand',
                'slug' => 'current-year-brand',
                'founded_year' => (int) now()->year,
                'support_url' => 'http://support.example.com/',
                'contact_email' => 'hello@example.com',
                'primary_color' => '#ff0000',
            ])
            ->assertRedirect(route('central.brands.edit', $brand));

        $brand->refresh();
        $this->assertSame((int) now()->year, $brand->founded_year);
        $this->assertSame('http://support.example.com/', $brand->support_url);
        $this->assertSame('#FF0000', $brand->primary_color);
        $this->assertSame(1, AuditLogEntry::query()->count());
    }
}
