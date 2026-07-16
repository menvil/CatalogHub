<?php

namespace App\Http\Requests\CentralAdmin\Translations;

use App\Services\Translations\AllowedTranslationStatuses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class SaveTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    final public function rules(AllowedTranslationStatuses $statuses): array
    {
        return $this->translationRules() + [
            'status' => [
                'nullable',
                'string',
                Rule::in($statuses->for($this->currentTranslation())),
            ],
        ];
    }

    /** @return array<string, mixed> */
    final public function payload(): array
    {
        return $this->validated();
    }

    /** @return array<string, list<mixed>> */
    abstract protected function translationRules(): array;

    abstract protected function currentTranslation(): ?Model;
}
