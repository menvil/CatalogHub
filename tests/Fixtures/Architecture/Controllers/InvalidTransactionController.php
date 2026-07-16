<?php

namespace App\Http\Controllers\ArchitectureFixtures;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

final class InvalidTransactionController extends Controller
{
    public function __invoke(): void
    {
        DB::transaction(static fn (): null => null);
    }
}
