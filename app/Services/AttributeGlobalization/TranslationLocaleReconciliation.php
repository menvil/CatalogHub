<?php

namespace App\Services\AttributeGlobalization;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\SchemaConsumerDecision;
use App\Models\Locale;
use App\Models\MeasurementUnit;
use App\Models\Translations\AttributeOptionTranslation;
use App\Models\Translations\AttributeSectionTranslation;
use App\Models\Translations\AttributeTranslation;
use App\Models\Translations\CategoryTranslation;
use App\Models\Translations\ProductTranslation;
use App\Models\Translations\UnitTranslation;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/** Explicit Locale-ID correction; never selects, combines or deletes translated text. */
final class TranslationLocaleReconciliation
{
    /** @var array<string, array{class-string<Model>, string}> */
    public const OWNERS = [
        'category_translations' => [CategoryTranslation::class, 'category_id'],
        'attribute_translations' => [AttributeTranslation::class, 'attribute_definition_id'],
        'attribute_option_translations' => [AttributeOptionTranslation::class, 'attribute_option_id'],
        'attribute_section_translations' => [AttributeSectionTranslation::class, 'attribute_section_id'],
        'unit_translations' => [UnitTranslation::class, 'measurement_unit_id'],
        'product_translations' => [ProductTranslation::class, 'product_id'],
    ];

    /** @param array<string, mixed> $decision
     * @return list<int>
     */
    public function categories(array $decision): array
    {
        $row = $this->row($decision);
        $owner = $row->getAttribute(self::OWNERS[$decision['owner_table']][1]);

        return match ($decision['owner_table']) {
            'category_translations' => [$owner],
            'attribute_section_translations' => AttributeSection::query()->whereKey($owner)->pluck('central_category_id')->all(),
            'product_translations' => CentralProduct::query()->whereKey($owner)->whereNotNull('central_category_id')->pluck('central_category_id')->all(),
            'attribute_translations' => CategoryAttributeAssignment::query()->where('attribute_definition_id', $owner)->pluck('central_category_id')->all(),
            'attribute_option_translations' => CategoryAttributeAssignment::query()->whereIn('attribute_definition_id', AttributeOption::query()->whereKey($owner)->select('attribute_definition_id'))->pluck('central_category_id')->all(),
            'unit_translations' => CategoryAttributeAssignment::query()->whereIn('attribute_definition_id', AttributeDefinition::query()->whereIn('measurement_dimension_id', MeasurementUnit::query()->whereKey($owner)->select('dimension_id'))->select('id'))->pluck('central_category_id')->all(),
            default => throw ValidationException::withMessages(['plan' => 'Unsupported typed translation owner.']),
        };
    }

    /** @param array<string, mixed> $decision */
    public function apply(array $decision, string $hash, User $actor, bool $audit): void
    {
        $row = $this->row($decision);
        Locale::query()->whereKey($decision['locale_id'])->lockForUpdate()->firstOrFail();
        $previous = $row->getAttribute('locale_id');
        if ($previous !== $decision['expected_locale_id']) {
            throw ValidationException::withMessages(['plan' => 'Translation Locale identity evidence changed.']);
        }
        if ($previous === $decision['locale_id']) {
            throw ValidationException::withMessages(['plan' => 'Locale reconciliation must explicitly correct an identity.']);
        }
        [$model, $owner] = self::OWNERS[$decision['owner_table']];
        if ($model::query()->where($owner, $row->getAttribute($owner))->where('locale_id', $decision['locale_id'])->whereKeyNot($row->getKey())->exists()) {
            throw ValidationException::withMessages(['plan' => 'Locale correction would collapse translated rows; no winner may be selected.']);
        }
        // Old code, text, approval and hash remain immutable evidence on this row.
        SchemaConsumerDecision::query()->create(['owner_type' => 'translation_locale:'.$decision['owner_table'].':'.$hash, 'owner_id' => $row->getKey(),
            'decision' => 'locale_id:'.($previous ?? 'null').':'.$decision['locale_id'], 'plan_hash' => $hash, 'actor_id' => $actor->id]);
        $row->forceFill(['locale_id' => $decision['locale_id']])->saveOrFail();
        if ($audit) {
            app(AuditRecorder::class)->record(AuditAction::CatalogAttributeIdentityReconciled, AuditContext::Central, $actor, $row, null,
                ['translation_id' => $row->getKey(), 'owner_table' => $decision['owner_table'], 'locale_id' => $previous],
                ['translation_id' => $row->getKey(), 'owner_table' => $decision['owner_table'], 'locale_id' => $decision['locale_id'], 'changed_fields' => ['locale_id']]);
        }
    }

    /** @param array<string, mixed> $decision */
    private function row(array $decision): Model
    {
        $model = self::OWNERS[$decision['owner_table']][0];

        return $model::query()->whereKey($decision['translation_id'])->lockForUpdate()->firstOrFail();
    }
}
