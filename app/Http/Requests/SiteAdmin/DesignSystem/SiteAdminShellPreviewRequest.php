<?php

declare(strict_types=1);

namespace App\Http\Requests\SiteAdmin\DesignSystem;

use App\Support\DesignSystem\SiteAdminShellFixture;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SiteAdminShellPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'state' => ['nullable', 'string', Rule::in(SiteAdminShellFixture::STATES)],
            'acceptance' => ['nullable', 'boolean'],
        ];
    }

    public function shellState(): string
    {
        $state = $this->validated('state');

        return is_string($state) ? $state : 'default';
    }

    public function acceptanceRequested(): bool
    {
        return in_array($this->validated('acceptance', false), [true, 1, '1'], true);
    }
}
