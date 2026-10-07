<?php

namespace App\Enums;

enum SchemaMutationOrigin: string
{
    case DisplayRuleConfigured = 'display_rule.configure';
    case FacetConfigured = 'facet.configure';
    case ComparisonConfigured = 'comparison.configure';
    case AttributeAssigned = 'assignment.assign';
    case AssignmentConfigured = 'assignment.configure';
    case AssignmentMoved = 'assignment.move';
    case AttributeUnassigned = 'assignment.unassign';
    case GlobalAttributeUpdated = 'global_attribute.update';
    case SectionCreated = 'section.create';
    case SectionUpdated = 'section.update';
    case SectionDeleted = 'section.delete';
    case AttributeCreated = 'attribute.create';
    case AttributeUpdated = 'attribute.update';
    case AttributeMoved = 'attribute.move';
    case OptionCreated = 'option.create';
    case OptionUpdated = 'option.update';
    case OptionDeleted = 'option.delete';
    case SchemaCloned = 'schema.clone';
}
