<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin;

use App\Support\Presentation\SafeExternalRecordUrl;
use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCentralBrandExternalIdentityRequest extends FormRequest
{
    private bool $originalExternalIdIsInvalid = false;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $externalId = $this->input('external_id');
        if (is_string($externalId)) {
            $this->originalExternalIdIsInvalid = preg_match('/\p{Cc}/u', $externalId) !== 0;

            if (! $this->originalExternalIdIsInvalid) {
                $this->merge(['external_id' => trim($externalId)]);
            }
        }

        if ($this->input('external_url') === '') {
            $this->merge(['external_url' => null]);
        }
    }

    protected function failedValidation(Validator $validator): void
    {
        $this->session()->flash('external_identity_modal', 'add');
        $this->session()->flash('external_identity_errors', $validator->errors()->toArray());

        parent::failedValidation($validator);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'import_source_id' => [
                'required',
                'integer',
                Rule::exists('import_sources', 'id')->where(
                    static fn (Builder $query): Builder => $query->where('status', 'active'),
                ),
            ],
            'external_id' => ['required', 'string', 'max:255', $this->validExternalId(...)],
            'external_url' => ['nullable', 'string', 'max:2048', $this->safeExternalUrl(...)],
        ];
    }

    public function importSourceId(): int
    {
        return (int) $this->validated('import_source_id');
    }

    public function externalId(): string
    {
        return (string) $this->validated('external_id');
    }

    public function externalUrl(): ?string
    {
        $value = $this->validated('external_url');

        return is_string($value) ? $value : null;
    }

    private function validExternalId(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $this->originalExternalIdIsInvalid || trim($value) === '') {
            $fail('The external ID must be nonblank and contain no control characters.');
        }
    }

    private function safeExternalUrl(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value !== null && ! SafeExternalRecordUrl::allows($value)) {
            $fail('The external record URL must be an HTTP or HTTPS URL without embedded credentials.');
        }
    }
}
