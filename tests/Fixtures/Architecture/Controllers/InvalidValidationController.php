<?php

namespace App\Http\Controllers\ArchitectureFixtures;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class InvalidValidationController extends Controller
{
    public function requestValidation(Request $request): void
    {
        $request->validate([]);
    }

    /** @param array<string, mixed> $data */
    public function facadeValidation(array $data): void
    {
        Validator::make($data, []);
    }

    /** @param array<string, mixed> $data */
    public function helperValidation(array $data): void
    {
        validator($data, []);
    }
}
