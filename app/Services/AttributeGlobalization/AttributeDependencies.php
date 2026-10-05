<?php

namespace App\Services\AttributeGlobalization;

use App\Models\AttributeDisplayRule;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\ContentRelation;
use App\Models\FacetDefinition;
use App\Models\Imports\AttributeMapping;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\Translations\AttributeTranslation;

final class AttributeDependencies
{
    /** @return list<string> */
    public function forDefinition(AttributeDefinition $definition, ?int $categoryId = null): array
    {
        $id = $definition->id;
        $dependencies = [];
        $values = CentralProductAttributeValue::query()->from('central_product_attribute_values as v')->toBase()->join('central_products as p', 'p.id', '=', 'v.central_product_id')->where('v.attribute_definition_id', $id);
        if ($categoryId !== null) {
            $values->where('p.central_category_id', $categoryId);
        }
        if ($values->exists()) {
            $dependencies[] = 'product_values';
        }
        // Conservatively include all mapped rows: unreviewed/in-flight rows also retain meaning.
        $mappings = AttributeMapping::query()->toBase()->where('attribute_definition_id', $id);
        if ($categoryId !== null) {
            $mappings->where('category_id', $categoryId);
        }
        if ($mappings->exists()) {
            $dependencies[] = 'import_mappings';
        }
        $facets = FacetDefinition::query()->toBase()->where('attribute_definition_id', $id);
        if ($categoryId !== null) {
            $facets->where('category_id', $categoryId);
        }
        if ($facets->exists()) {
            $dependencies[] = 'facet_definitions';
        }
        foreach (['attribute_options' => 'options', 'attribute_display_rules' => 'display_rules', 'attribute_translations' => 'translations'] as $table => $reason) {
            // Options/translations belong globally; they do not by themselves prevent local unassignment.
            if (($categoryId === null || $reason === 'display_rules') && (match ($table) {
                'attribute_options' => AttributeOption::class, 'attribute_display_rules' => AttributeDisplayRule::class, default => AttributeTranslation::class
            })::query()->where('attribute_definition_id', $id)->exists()) {
                $dependencies[] = $reason;
            }
        }
        if (ContentRelation::query()->toBase()->where('related_type', 'attribute')->where('related_id', $id)->exists()) {
            $dependencies[] = 'content_relations';
        }
        $assignmentCategoryIds = $definition->assignments()->pluck('central_category_id')->all();
        $drafts = NormalizedProductDraft::query()->whereIn('status', ['pending_review', 'approved'])->orderBy('id');
        if ($categoryId !== null) {
            $drafts->where('category_id', $categoryId);
        }
        foreach ($drafts->cursor() as $draft) {
            $candidates = $draft->getAttribute('attributes_json');
            if (! is_array($candidates)) {
                $dependencies[] = 'unreadable_draft_payload';
                break;
            }
            foreach ($candidates as $candidate) {
                if (! is_array($candidate) || ((int) ($candidate['attribute_definition_id'] ?? 0) === $id)
                    || (($candidate['code'] ?? null) === $definition->code && ($categoryId !== null || (int) $draft->category_id === (int) $definition->central_category_id || in_array($draft->category_id, $assignmentCategoryIds, true)))) {
                    $dependencies[] = 'normalized_drafts';
                    break 2;
                }
            }
        }
        if ($categoryId === null && count($assignmentCategoryIds) > 1) {
            $dependencies[] = 'multiple_category_assignments';
        }
        if ($categoryId !== null && (bool) $definition->is_comparable) {
            $dependencies[] = 'legacy_comparison_configuration';
        }

        return array_values(array_unique($dependencies));
    }
}
