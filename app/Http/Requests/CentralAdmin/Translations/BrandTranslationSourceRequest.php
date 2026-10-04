<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin\Translations;

use Illuminate\Foundation\Http\FormRequest;

final class BrandTranslationSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return self::sourceRules();
    }

    /** @return array<string, list<string>> */
    public static function sourceRules(): array
    {
        return ['source' => ['nullable', 'string', 'max:35']];
    }
}
