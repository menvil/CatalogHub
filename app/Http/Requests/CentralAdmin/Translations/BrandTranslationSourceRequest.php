<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin\Translations;

use App\Models\Locale;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BrandTranslationSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return self::sourceRules($this->route('locale'));
    }

    /** @return array<string, list<mixed>> */
    public static function sourceRules(mixed $target): array
    {
        return ['source' => [
            'bail', 'nullable', 'string', 'max:35',
            Rule::notIn($target instanceof Locale ? [$target->code] : []),
            static function (string $attribute, mixed $value, Closure $fail): void {
                // SQL collations can match a differently cased code. Require
                // the exact stored code, as the editor's source selection does.
                if (! is_string($value) || Locale::query()->active()->where('code', $value)->value('code') !== $value) {
                    $fail('Choose an active source language different from the target.');
                }
            },
        ]];
    }
}
