<?php

namespace App\Enums;

enum ContentRelationTargetType: string
{
    case Product = 'product';
    case Category = 'category';
    case Brand = 'brand';
    case Attribute = 'attribute';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
