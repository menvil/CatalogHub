<?php

namespace App\Actions\CategorySchema;

use App\Models\CentralCatalog\AttributeSection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class UpdateAttributeSectionAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(AttributeSection $section, array $data): AttributeSection
    {
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:255',
                'regex:/\A[a-z][a-z0-9_]*\z/',
                Rule::unique('attribute_sections', 'code')
                    ->where('central_category_id', $section->central_category_id)
                    ->ignore($section->getKey()),
            ],
            'position' => ['nullable', 'integer', 'min:0', 'max:'.AttributeSection::MAX_POSITION],
            'display_style' => ['nullable', Rule::in(AttributeSection::DISPLAY_STYLES)],
            'is_collapsible' => ['nullable', 'boolean'],
            'is_visible' => ['nullable', 'boolean'],
        ])->validate();

        $section->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'position' => $validated['position'] ?? $section->position,
            'display_style' => $validated['display_style'] ?? $section->display_style,
            'is_collapsible' => $validated['is_collapsible'] ?? $section->is_collapsible,
            'is_visible' => $validated['is_visible'] ?? $section->is_visible,
        ]);

        return $section;
    }
}
