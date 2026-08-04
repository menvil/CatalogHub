<?php

namespace App\Http\Controllers\ArchitectureFixtures;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

final class UnvalidatedInputController
{
    /** @return list<mixed> */
    public function invalid(Request $request, ?Request $optional): array
    {
        return [
            $request->input('status'),
            $request->string('type'),
            $request['page'],
            $request->sort,
            $optional?->query('filter'),
            request('search'),
        ];
    }

    /** @return list<mixed> */
    public function valid(FormRequest $request, NonRequestInput $input): array
    {
        return [
            $request->validated(),
            $request->safe(),
            $request->getHost(),
            $request->user(),
            $input->input('status'),
        ];
    }
}

final class NonRequestInput
{
    public function input(string $key): string
    {
        return $key;
    }
}
