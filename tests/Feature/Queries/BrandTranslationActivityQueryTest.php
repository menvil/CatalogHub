<?php

declare(strict_types=1);

namespace Tests\Feature\Queries;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\User;
use App\Queries\Translations\BrandTranslationActivityQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\DatabaseQueryCounter;
use Tests\TestCase;

final class BrandTranslationActivityQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_is_brand_and_locale_specific_bounded_newest_first_and_actor_eager_loaded(): void
    {
        $actor = User::factory()->create(['name' => 'Translation Manager']);
        $brand = CentralBrand::factory()->create();
        $otherBrand = CentralBrand::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $otherLocale = Locale::factory()->create(['code' => 'fr-FR']);

        foreach (range(1, 10) as $offset) {
            $this->event($brand, $locale, $actor, "2026-08-28 10:{$offset}:00", $offset);
        }
        $this->event($brand, $otherLocale, $actor, '2026-08-28 11:00:00', 100);
        $this->event($otherBrand, $locale, $actor, '2026-08-28 12:00:00', 101);
        AuditLogEntry::factory()->central()->create([
            'actor_id' => $actor->id,
            'action' => AuditAction::CatalogBrandUpdated->value,
            'subject_type' => $brand->getMorphClass(),
            'subject_id' => (string) $brand->id,
            'after_json' => ['locale' => $locale->code],
            'created_at' => '2026-08-28 13:00:00',
        ]);

        $measured = DatabaseQueryCounter::measure(
            fn () => app(BrandTranslationActivityQuery::class)->forBrandAndLocale($brand, $locale),
        );
        $activity = $measured['result'];

        $this->assertCount(BrandTranslationActivityQuery::LIMIT, $activity);
        $this->assertSame(2, $measured['count']);
        $this->assertSame(range(10, 3), $activity->pluck('after_json.sequence')->all());
        $this->assertTrue($activity->every(fn (AuditLogEntry $event): bool => $event->relationLoaded('actor')));
        $this->assertTrue($activity->every(fn (AuditLogEntry $event): bool => $event->subject_id === (string) $brand->id));
        $this->assertTrue($activity->every(fn (AuditLogEntry $event): bool => $event->after_json['locale'] === 'de-DE'));
    }

    public function test_activity_ui_shows_semantics_without_rendering_raw_localized_content(): void
    {
        $actor = User::factory()->create(['role' => UserRole::Translator, 'name' => 'Translation Manager']);
        $brand = CentralBrand::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        AuditLogEntry::factory()->central()->create([
            'actor_id' => $actor->id,
            'action' => AuditAction::CatalogBrandTranslationSaved->value,
            'subject_type' => $brand->getMorphClass(),
            'subject_id' => (string) $brand->id,
            'after_json' => [
                'translation_id' => 15,
                'locale' => 'de-DE',
                'status' => 'human_reviewed',
                'changed_fields' => ['name', 'short_description'],
                'localized_copy' => 'SECRET RAW LOCALIZED COPY',
            ],
        ]);

        $this->actingAs($actor)
            ->get(route('central.brands.translations.edit', [$brand, $locale->code]))
            ->assertOk()
            ->assertSee('Translation created')
            ->assertSee('Translation Manager')
            ->assertSee('Changed: name, short description')
            ->assertDontSee('SECRET RAW LOCALIZED COPY');
    }

    private function event(CentralBrand $brand, Locale $locale, User $actor, string $createdAt, int $sequence): void
    {
        AuditLogEntry::factory()->central()->create([
            'actor_id' => $actor->id,
            'action' => AuditAction::CatalogBrandTranslationSaved->value,
            'subject_type' => $brand->getMorphClass(),
            'subject_id' => (string) $brand->id,
            'after_json' => [
                'translation_id' => $sequence,
                'locale' => $locale->code,
                'status' => 'human_reviewed',
                'changed_fields' => ['name'],
                'sequence' => $sequence,
            ],
            'created_at' => $createdAt,
        ]);
    }
}
