<?php

namespace App\Enums;

enum SchemaMutationOrigin: string
{
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
