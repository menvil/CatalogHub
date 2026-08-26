<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCentralBrandTagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->exists('tags')) {
            $this->merge(['tags' => []]);
        }
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'tags' => ['present', 'array', 'max:20'],
            'tags.*' => ['required', 'string', 'max:80'],
        ];
    }

    /** @return list<string> */
    public function tagNames(): array
    {
        $tags = $this->validated('tags');
        assert(is_array($tags));

        return array_values($tags);
    }
}
