<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin\DesignSystem;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ComponentGalleryRequest extends FormRequest
{
    private const SECTIONS = [
        'actions',
        'forms',
        'tables',
        'indicators',
        'layout',
        'feedback',
        'states',
        'overlays',
        'advanced',
        'acceptance',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'mode' => ['nullable', 'string', Rule::in(['components'])],
            'section' => ['nullable', 'string', Rule::in(self::SECTIONS)],
            'acceptance' => ['nullable', 'boolean'],
        ];
    }

    public function componentMode(): bool
    {
        return $this->validated('mode') === 'components';
    }

    public function componentSection(): string
    {
        $section = $this->validated('section');

        return is_string($section) ? $section : 'forms';
    }

    public function hasComponentSection(): bool
    {
        return is_string($this->validated('section'));
    }

    public function acceptanceRequested(): bool
    {
        return in_array($this->validated('acceptance', false), [true, 1, '1'], true);
    }
}
