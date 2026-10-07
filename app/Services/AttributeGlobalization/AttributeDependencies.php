<?php

namespace App\Services\AttributeGlobalization;

use App\Models\AttributeDisplayRule;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\CategoryComparisonAttribute;
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
        $assignmentRows = $definition->assignments()->get(['id', 'central_category_id']);
        $assignmentIds = ($categoryId === null ? $assignmentRows : $assignmentRows->where('central_category_id', $categoryId))->pluck('id');
        $mappings = AttributeMapping::query()->toBase()->whereIn('category_attribute_assignment_id', $assignmentIds);
        if ($categoryId !== null) {
            $mappings->where('category_id', $categoryId);
        }
        if ($mappings->exists()) {
            $dependencies[] = 'import_mappings';
        }
        $facets = FacetDefinition::query()->toBase()->whereIn('category_attribute_assignment_id', $assignmentIds);
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
        $assignmentCategoryIds = $assignmentRows->pluck('central_category_id')->all();
        $drafts = NormalizedProductDraft::query()->whereIn('status', ['pending_review', 'approved'])->orderBy('id');
        if ($categoryId !== null) {
            $drafts->where(fn ($query) => $query->where('category_id', $categoryId)
                ->orWhere(fn ($query) => $query->whereNull('category_id')->whereHas('matchedCentralProduct', fn ($query) => $query->where('central_category_id', $categoryId))));
        }
        foreach ($drafts->cursor() as $draft) {
            $candidates = $draft->getAttribute('attributes_json');
            if (! is_array($candidates)) {
                $dependencies[] = 'unreadable_draft_payload';
                break;
            }
            foreach ($candidates as $candidate) {
                if (! is_array($candidate) || in_array($candidate['category_attribute_assignment_id'] ?? null, $assignmentIds->all(), true) || ((int) ($candidate['attribute_definition_id'] ?? 0) === $id)
                    || (($candidate['code'] ?? null) === $definition->code && ($categoryId !== null || in_array($draft->category_id, $assignmentCategoryIds, true)))) {
                    $dependencies[] = 'normalized_drafts';
                    break 2;
                }
            }
        }
        if ($categoryId === null && count($assignmentCategoryIds) > 1) {
            $dependencies[] = 'multiple_category_assignments';
        }
        if (CategoryComparisonAttribute::query()->whereIn('category_attribute_assignment_id', $assignmentIds)->exists()) {
            $dependencies[] = 'comparison_configuration';
        }

        return array_values(array_unique($dependencies));
    }
}
