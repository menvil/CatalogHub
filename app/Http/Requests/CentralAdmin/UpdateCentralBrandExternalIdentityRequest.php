<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin;

use App\Support\Presentation\SafeExternalRecordUrl;
use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateCentralBrandExternalIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('external_url') === '') {
            $this->merge(['external_url' => null]);
        }
    }

    protected function failedValidation(Validator $validator): void
    {
        $identity = $this->route('externalIdentity');
        $identityId = is_object($identity) && method_exists($identity, 'getKey') ? $identity->getKey() : null;
        $this->session()->flash('external_identity_modal', $identityId === null ? null : (string) $identityId);
        $this->session()->flash('external_identity_errors', $validator->errors()->toArray());

        parent::failedValidation($validator);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'external_id' => ['required', 'string', 'max:255', $this->validExternalId(...)],
            'external_url' => ['nullable', 'string', 'max:2048', $this->safeExternalUrl(...)],
        ];
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
        if (! is_string($value) || trim($value) === '' || preg_match('/\p{Cc}/u', trim($value)) === 1) {
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
