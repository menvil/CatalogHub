<?php

namespace App\Models\CentralCatalog;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['plan_hash', 'actor_id', 'decision_count', 'applied_at'])]
final class SchemaConsumerReconciliationPlan extends Model
{
    protected $table = 'schema_consumer_reconciliation_plans';

    public $timestamps = false;

    protected $primaryKey = 'plan_hash';

    public $incrementing = false;

    protected $keyType = 'string';
}
