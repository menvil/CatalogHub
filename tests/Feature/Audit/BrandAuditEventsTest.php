<?php

declare(strict_types=1);

namespace Tests\Feature\Audit;

use App\Actions\CentralCatalog\ActivateCentralBrandAction;
use App\Actions\CentralCatalog\ArchiveCentralBrandAction;
use App\Actions\CentralCatalog\RemoveCentralBrandLogoAction;
use App\Actions\CentralCatalog\RestoreCentralBrandAction;
use App\Actions\CentralCatalog\SetCentralBrandLogoAction;
use App\Actions\CentralCatalog\UpdateCentralBrandAction;
use App\Actions\Translations\SaveBrandTranslationAction;
use App\Data\CentralCatalog\CentralBrandInput;
use App\Data\Translations\BrandTranslationInput;
use App\Enums\AuditAction;
use App\Enums\CentralBrandStatus;
use App\Enums\TranslationStatus;
use App\Enums\UserRole;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\MediaAsset;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\Support\CountryReference;
use Tests\TestCase;

final class BrandAuditEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_http_create_records_one_minimized_brand_event_with_actor_and_request_id(): void
    {
        $actor = User::factory()->centralAdmin()->create();

        $this->actingAs($actor)->withHeader('X-Request-ID', 'brand-create-request')->post(route('central.brands.store'), [
            'name' => 'Samsung',
            'slug' => 'samsung',
            'website_url' => 'https://www.samsung.com',
            'country_id' => CountryReference::id('KR'),
        ])->assertRedirect(route('central.brands.index'));

        $brand = CentralBrand::query()->sole();
        $entry = AuditLogEntry::query()->sole();
        $this->assertSame($actor->id, $entry->actor_id);
        $this->assertSame('central', $entry->context);
        $this->assertNull($entry->site_id);
        $this->assertSame(AuditAction::CatalogBrandCreated->value, $entry->action);
        $this->assertSame($brand->getMorphClass(), $entry->subject_type);
        $this->assertSame((string) $brand->id, $entry->subject_id);
        $this->assertNull($entry->before_json);
        $this->assertSame([
            'name' => 'Samsung',
            'slug' => 'samsung',
            'status' => 'draft',
            'website_url' => 'https://www.samsung.com',
            'country_code' => 'KR',
        ], $entry->after_json);
        $this->assertSame('brand-create-request', $entry->request_id);
    }

    public function test_update_records_changed_fields_only_and_no_op_is_silent(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create(['name' => 'Samsung', 'slug' => 'samsung', 'website_url' => 'https://old.example', 'country_id' => CountryReference::id('KR')]);
        $action = app(UpdateCentralBrandAction::class);

        $action->handle($actor, $brand, new CentralBrandInput('Samsung', 'samsung', true, 'https://new.example', true, CountryReference::id('KR')));
        $entry = AuditLogEntry::query()->sole();
        $this->assertSame(['website_url' => 'https://old.example'], $entry->before_json);
        $this->assertSame(['website_url' => 'https://new.example'], $entry->after_json);
        $this->assertStringNotContainsString('normalized_name', json_encode([$entry->before_json, $entry->after_json], JSON_THROW_ON_ERROR));

        $action->handle($actor, $brand->refresh(), new CentralBrandInput('Samsung', 'samsung', true, 'https://new.example', true, CountryReference::id('KR')));
        $this->assertSame(1, AuditLogEntry::query()->count());
    }

    public function test_country_audit_uses_semantic_alpha2_values_for_change_clear_and_no_op(): void
    {
        $actor = User::factory()->create();
        $kr = CountryReference::get('KR');
        $jp = CountryReference::get('JP');
        $brand = CentralBrand::factory()->create([
            'name' => 'Samsung',
            'slug' => 'samsung',
            'country_id' => $kr->id,
        ]);
        $action = app(UpdateCentralBrandAction::class);

        $action->handle($actor, $brand, new CentralBrandInput('Samsung', 'samsung', false, null, true, $jp->id));
        $first = AuditLogEntry::query()->sole();
        $this->assertSame(['country_code' => 'KR'], $first->before_json);
        $this->assertSame(['country_code' => 'JP'], $first->after_json);
        $this->assertArrayNotHasKey('country_id', $first->before_json);

        $action->handle($actor, $brand->refresh(), new CentralBrandInput('Samsung', 'samsung', false, null, true, $jp->id));
        $this->assertSame(1, AuditLogEntry::query()->count());

        $action->handle($actor, $brand->refresh(), new CentralBrandInput('Samsung', 'samsung', false, null, true, null));
        $clear = AuditLogEntry::query()->latest('id')->firstOrFail();
        $this->assertSame(['country_code' => 'JP'], $clear->before_json);
        $this->assertSame(['country_code' => null], $clear->after_json);
        $this->assertStringNotContainsString('country_id', json_encode([$clear->before_json, $clear->after_json], JSON_THROW_ON_ERROR));
    }

    public function test_lifecycle_events_capture_locked_status_changes_and_skip_no_ops_and_invalid_transition(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->draft()->create();

        app(ActivateCentralBrandAction::class)->handle($actor, $brand);
        app(ActivateCentralBrandAction::class)->handle($actor, $brand->refresh());
        app(ArchiveCentralBrandAction::class)->handle($actor, $brand->refresh());
        app(ArchiveCentralBrandAction::class)->handle($actor, $brand->refresh());
        app(RestoreCentralBrandAction::class)->handle($actor, $brand->refresh());
        app(RestoreCentralBrandAction::class)->handle($actor, $brand->refresh());

        $this->assertSame([
            AuditAction::CatalogBrandActivated->value,
            AuditAction::CatalogBrandArchived->value,
            AuditAction::CatalogBrandRestored->value,
        ], AuditLogEntry::query()->orderBy('id')->pluck('action')->all());
        $this->assertSame(['status' => 'draft'], AuditLogEntry::query()->firstOrFail()->before_json);

        $archived = CentralBrand::factory()->archived()->create();

        try {
            app(ActivateCentralBrandAction::class)->handle($actor, $archived);
            $this->fail('Expected invalid transition.');
        } catch (ValidationException) {
            $this->assertSame(3, AuditLogEntry::query()->count());
        }
    }

    public function test_logo_events_are_brand_centric_minimized_and_no_op_aware(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $first = MediaAsset::factory()->create();
        $second = MediaAsset::factory()->create();
        $set = app(SetCentralBrandLogoAction::class);

        $set->execute($actor, $brand, $first);
        $set->execute($actor, $brand, $first);
        $set->execute($actor, $brand, $second);
        app(RemoveCentralBrandLogoAction::class)($actor, $brand);
        app(RemoveCentralBrandLogoAction::class)($actor, $brand);

        $entries = AuditLogEntry::query()->orderBy('id')->get();
        $this->assertCount(3, $entries);
        $this->assertSame(['media_asset_id' => null], $entries[0]->before_json);
        $this->assertSame(['media_asset_id' => $first->id], $entries[0]->after_json);
        $this->assertSame(['media_asset_id' => $first->id], $entries[1]->before_json);
        $this->assertSame(['media_asset_id' => $second->id], $entries[1]->after_json);
        $this->assertSame(AuditAction::CatalogBrandLogoRemoved->value, $entries[2]->action);
        $this->assertSame($brand->getMorphClass(), $entries[2]->subject_type);
    }

    public function test_translation_event_attributes_translator_and_never_stores_content_values(): void
    {
        $actor = User::factory()->create(['role' => UserRole::Translator]);
        $brand = CentralBrand::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $input = new BrandTranslationInput('Samsung DE', 'Private tagline', 'Private short', 'Private long description', 'Private SEO title', 'Private SEO description', TranslationStatus::HumanReviewed);
        $action = app(SaveBrandTranslationAction::class);

        $translation = $action->handle($actor, $brand, $locale, $input);
        $action->handle($actor, $brand, $locale, $input);

        $entry = AuditLogEntry::query()->sole();
        $this->assertSame($actor->id, $entry->actor_id);
        $this->assertSame($brand->getMorphClass(), $entry->subject_type);
        $this->assertSame(AuditAction::CatalogBrandTranslationSaved->value, $entry->action);
        $this->assertSame($translation->id, $entry->after_json['translation_id']);
        $this->assertSame('de-DE', $entry->after_json['locale']);
        $serialized = json_encode([$entry->before_json, $entry->after_json], JSON_THROW_ON_ERROR);
        foreach (['Private tagline', 'Private short', 'Private long description', 'Private SEO title', 'Private SEO description'] as $content) {
            $this->assertStringNotContainsString($content, $serialized);
        }
    }

    public function test_audit_failure_rolls_back_update_and_archive(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->active()->create(['website_url' => 'https://old.example']);
        $audit = $this->createMock(AuditRecorder::class);
        $audit->method('record')->willThrowException(new RuntimeException('audit unavailable'));
        $this->app->instance(AuditRecorder::class, $audit);

        foreach ([
            fn () => app(UpdateCentralBrandAction::class)->handle($actor, $brand, new CentralBrandInput($brand->name, $brand->slug, true, 'https://new.example')),
            fn () => app(ArchiveCentralBrandAction::class)->handle($actor, $brand),
        ] as $mutation) {
            try {
                $mutation();
                $this->fail('Expected audit failure.');
            } catch (RuntimeException $exception) {
                $this->assertSame('audit unavailable', $exception->getMessage());
            }
        }

        $brand->refresh();
        $this->assertSame('https://old.example', $brand->website_url);
        $this->assertSame(CentralBrandStatus::Active, $brand->status);
        $this->assertSame(0, AuditLogEntry::query()->count());
    }

    public function test_unauthorized_and_invalid_http_mutations_write_no_brand_or_audit_data(): void
    {
        $brand = CentralBrand::factory()->create();
        $translator = User::factory()->create(['role' => UserRole::Translator]);

        $this->actingAs($translator)->patch(route('central.brands.update', $brand), ['name' => 'Forbidden'])->assertForbidden();
        $this->assertSame($brand->name, $brand->fresh()->name);

        $manager = User::factory()->create();
        $this->actingAs($manager)->post(route('central.brands.store'), ['name' => ''])->assertSessionHasErrors('name');
        $this->actingAs($manager)->post(route('central.brands.store'), [
            'name' => 'Duplicate slug candidate',
            'slug' => $brand->slug,
        ])->assertSessionHasErrors('slug');
        $this->assertSame(0, AuditLogEntry::query()->count());
    }
}
