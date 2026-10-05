<?php

namespace App\Models\CentralCatalog;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
final class AttributeDefinitionCrosswalk extends Model
{
    protected $table = 'attribute_definition_crosswalks';

    protected $primaryKey = 'legacy_definition_id';

    public $timestamps = false;

    public $incrementing = false;
}
