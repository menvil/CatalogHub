<?php

namespace App\Models\CentralCatalog;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['owner_type', 'owner_id', 'decision', 'plan_hash', 'actor_id'])]
final class SchemaConsumerDecision extends Model {}
