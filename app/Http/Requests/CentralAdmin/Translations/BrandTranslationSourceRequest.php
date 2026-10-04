<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin\Translations;

use App\Models\Locale;
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
            Rule::exists(Locale::class, 'code')->where('is_active', true),
        ]];
    }
}
