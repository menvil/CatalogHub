<?php

namespace App\Actions\CategorySchema;

use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class CreateAttributeSectionAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function handle(CentralCategory $category, array $data): AttributeSection
    {
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('attribute_sections', 'code')
                    ->where('central_category_id', $category->getKey()),
            ],
            'position' => ['nullable', 'integer', 'min:0'],
            'display_style' => ['nullable', 'string', 'max:255'],
            'is_collapsible' => ['nullable', 'boolean'],
            'is_visible' => ['nullable', 'boolean'],
        ])->validate();

        $position = $validated['position']
            ?? ((int) $category->attributeSections()->max('position') + 1);

        return AttributeSection::query()->create([
            'central_category_id' => $category->getKey(),
            'parent_id' => null,
            'code' => $validated['code'],
            'name' => $validated['name'],
            'position' => $position,
            'display_style' => $validated['display_style'] ?? 'table',
            'is_collapsible' => $validated['is_collapsible'] ?? true,
            'is_visible' => $validated['is_visible'] ?? true,
        ]);
    }
}
