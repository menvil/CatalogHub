<?php

namespace App\Models\CentralCatalog;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['section_id', 'plan_hash', 'legacy_parent_id', 'legacy_position', 'target_position'])]
final class SchemaConsumerSectionDecision extends Model
{
    protected $table = 'schema_consumer_section_decisions';

    protected $primaryKey = 'section_id';

    public $incrementing = false;

    public $timestamps = false;
}
