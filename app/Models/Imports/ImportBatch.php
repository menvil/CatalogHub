<?php

namespace App\Models\Imports;

use Illuminate\Database\Eloquent\Model;

final class ImportBatch extends Model
{
    // The batch model is completed in P09-010. This counterpart keeps the
    // ImportSource relationship executable while tasks land atomically.
}
