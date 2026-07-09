<?php

namespace App\Services\CategorySchema;

use App\DTO\CategorySchema\CategorySchemaIssue;
use App\DTO\CategorySchema\CategorySchemaValidationResult;
use App\Enums\AttributeDataType;
use App\Enums\CategorySchemaIssueSeverity;
use App\Models\CentralCatalog\CentralCategory;

final class CategorySchemaValidator
{
    public function validate(CentralCategory $category): CategorySchemaValidationResult
    {
        $result = new CategorySchemaValidationResult;

        $category->loadMissing([
            'attributeSections' => fn ($query) => $query->ordered(),
            'attributeSections.attributes' => fn ($query) => $query->ordered(),
            'attributeDefinitions.options',
        ]);

        foreach ($category->attributeSections as $section) {
            if ($section->attributes->isEmpty()) {
                $result->add(new CategorySchemaIssue(
                    severity: CategorySchemaIssueSeverity::Warning,
                    code: 'empty_section',
                    message: "Section [{$section->code}] has no attributes.",
                    entityType: 'attribute_section',
                    entityId: $section->id,
                ));
            }
        }

        foreach ($category->attributeDefinitions as $attribute) {
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

            if ($attribute->is_required && ! $attribute->is_visible) {
                $result->add(new CategorySchemaIssue(
                    severity: CategorySchemaIssueSeverity::Warning,
                    code: 'hidden_required_attribute',
                    message: "Required attribute [{$attribute->code}] is hidden.",
                    entityType: 'attribute_definition',
                    entityId: $attribute->id,
                ));
            }

            if ($attribute->is_filterable && $this->isComplexType($attribute->data_type)) {
                $result->add(new CategorySchemaIssue(
                    severity: CategorySchemaIssueSeverity::Warning,
                    code: 'filterable_complex_attribute',
                    message: "Attribute [{$attribute->code}] is filterable with a complex data type.",
                    entityType: 'attribute_definition',
                    entityId: $attribute->id,
                ));
            }

            if ($attribute->is_sortable && $this->isComplexType($attribute->data_type)) {
                $result->add(new CategorySchemaIssue(
                    severity: CategorySchemaIssueSeverity::Warning,
                    code: 'sortable_complex_attribute',
                    message: "Attribute [{$attribute->code}] is sortable with a complex data type.",
                    entityType: 'attribute_definition',
                    entityId: $attribute->id,
                ));
            }
        }

        return $result;
    }

    private function isComplexType(AttributeDataType $type): bool
    {
        return in_array($type, [AttributeDataType::Text, AttributeDataType::Json], true);
    }
}
