<?php

namespace App\Services\CategorySchema;

use App\DTO\CategorySchema\CategorySchemaIssue;
use App\DTO\CategorySchema\CategorySchemaValidationResult;
use App\Enums\AttributeDataType;
use App\Enums\CategorySchemaIssueSeverity;
use App\Enums\FacetSourceType;
use App\Models\CentralCatalog\CategoryComparisonAttribute;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\FacetDefinition;
use App\Services\AttributeGlobalization\AttributeIdentityReservation;

final class CategorySchemaValidator
{
    public function validate(CentralCategory $category): CategorySchemaValidationResult
    {
        $result = new CategorySchemaValidationResult;

        $category->load([
            'attributeSections' => fn ($query) => $query->ordered(),
            'attributeSections.assignments',
            'attributeAssignments.definition.options', 'attributeAssignments.definition.canonicalMeasurementUnit', 'attributeAssignments.section',
        ]);

        foreach ($category->attributeSections as $section) {
            if ($section->parent_id !== null) {
                $result->add(new CategorySchemaIssue(CategorySchemaIssueSeverity::Error, 'invalid_section_shape', 'Section shape requires reviewed flattening/order reconciliation.', 'attribute_section', $section->id));
            }
            if ($section->assignments->isEmpty()) {
                $result->add(new CategorySchemaIssue(
                    severity: CategorySchemaIssueSeverity::Warning,
                    code: 'empty_section',
                    message: "Section [{$section->code}] has no attributes.",
                    entityType: 'attribute_section',
                    entityId: $section->id,
                ));
            }
        }

        foreach ($category->attributeAssignments as $assignment) {
            $attribute = $assignment->definition;
            if (! app(AttributeIdentityReservation::class)->resolved($attribute)
                || ($assignment->section !== null && $assignment->section->central_category_id !== $category->id)
                || (($attribute->measurement_dimension_id === null) !== ($attribute->canonical_measurement_unit_id === null))
                || ($attribute->canonicalMeasurementUnit !== null && ($attribute->canonicalMeasurementUnit->dimension_id !== $attribute->measurement_dimension_id || ! in_array($attribute->data_type, [AttributeDataType::Integer, AttributeDataType::Decimal], true)))) {
                $result->add(new CategorySchemaIssue(CategorySchemaIssueSeverity::Error, 'invalid_assignment_identity', 'Unresolved or incompatible assignment meaning.', 'category_attribute_assignment', $assignment->id));
            }
            $visibleOptionsCount = $attribute->options->where('is_visible', true)->count();

            if ($attribute->data_type->allowsOptions() && $visibleOptionsCount === 0) {
                $result->add(new CategorySchemaIssue(
                    severity: CategorySchemaIssueSeverity::Warning,
                    code: 'enum_without_visible_options',
                    message: "Enum attribute [{$attribute->code}] has no visible options.",
                    entityType: 'attribute_definition',
                    entityId: $attribute->id,
                ));
            }

            if (! $attribute->data_type->allowsOptions() && $attribute->options->isNotEmpty()) {
                $result->add(new CategorySchemaIssue(
                    severity: CategorySchemaIssueSeverity::Error,
                    code: 'options_on_non_enum_attribute',
                    message: "Non-enum attribute [{$attribute->code}] has options.",
                    entityType: 'attribute_definition',
                    entityId: $attribute->id,
                ));
            }

            if ($assignment->is_required && ! $assignment->is_visible) {
                $result->add(new CategorySchemaIssue(
                    severity: CategorySchemaIssueSeverity::Warning,
                    code: 'hidden_required_attribute',
                    message: "Required attribute [{$attribute->code}] is hidden.",
                    entityType: 'attribute_definition',
                    entityId: $attribute->id,
                ));
            }

            if ($assignment->is_sortable && $this->isComplexType($attribute->data_type)) {
                $result->add(new CategorySchemaIssue(
                    severity: CategorySchemaIssueSeverity::Warning,
                    code: 'sortable_complex_attribute',
                    message: "Attribute [{$attribute->code}] is sortable with a complex data type.",
                    entityType: 'attribute_definition',
                    entityId: $attribute->id,
                ));
            }
        }

        $assignmentIds = $category->attributeAssignments->pluck('id')->all();
        foreach (FacetDefinition::query()->where('category_id', $category->id)->orderBy('id')->get() as $facet) {
            if (($facet->source_type === FacetSourceType::Attribute && ! in_array($facet->category_attribute_assignment_id, $assignmentIds, true))
                || ($facet->source_type !== FacetSourceType::Attribute && $facet->category_attribute_assignment_id !== null)) {
                $result->add(new CategorySchemaIssue(CategorySchemaIssueSeverity::Error, 'invalid_facet_assignment', 'Facet assignment must belong to the same Category.', 'facet_definition', $facet->id));
            }
        }
        foreach (CategoryComparisonAttribute::query()->where('central_category_id', $category->id)->orderBy('id')->get() as $row) {
            if (! in_array($row->category_attribute_assignment_id, $assignmentIds, true)) {
                $result->add(new CategorySchemaIssue(CategorySchemaIssueSeverity::Error, 'invalid_comparison_assignment', 'Comparison assignment/order is invalid.', 'category_comparison_attribute', $row->id));
            }
        }

        return $result;
    }

    private function isComplexType(AttributeDataType $type): bool
    {
        return in_array($type, [AttributeDataType::Text, AttributeDataType::Json], true);
    }
}
