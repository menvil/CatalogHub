<?php

namespace App\Models\CentralCatalog;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
final class AttributeIdentityScope extends Model
{
    protected $table = 'attribute_identity_scopes';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public $incrementing = false;
}
