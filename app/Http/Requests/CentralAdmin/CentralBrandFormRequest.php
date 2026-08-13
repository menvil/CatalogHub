<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin;

use Illuminate\Foundation\Http\FormRequest;

final class CentralBrandFormRequest extends FormRequest
{
    /** @var list<string> */
    private const INPUT_FIELDS = ['name', 'slug', 'website_url', 'country_code'];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function brandInput(): array
    {
        return $this->only(self::INPUT_FIELDS);
    }
}
