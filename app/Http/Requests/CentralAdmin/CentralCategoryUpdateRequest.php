<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin;

use App\Data\CentralCatalog\CentralCategoryExpectedState;
use App\Data\CentralCatalog\CentralCategorySaveData;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Foundation\Http\FormRequest;

/**
 * CA-018 Edit submission. Hidden fields are expected-state tokens read when the
 * form loaded; the save action revalidates all of them under its locks.
 */
final class CentralCategoryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'min:1'],
            'expected_parent_id' => ['present', 'nullable', 'integer', 'min:1'],
            'expected_name' => ['required', 'string', 'max:255'],
            'expected_slug' => ['required', 'string', 'max:255'],
            'current_scope_revision' => ['required', 'integer', 'min:0'],
            'destination_scope_revision' => ['required', 'integer', 'min:0'],
            'sibling_ids' => ['required', 'array', 'list', 'min:1'],
            'sibling_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'display_position' => ['required', 'integer', 'min:0'],
            'slug_impact_fingerprint' => ['nullable', 'string', 'regex:/\A[0-9a-f]{64}\z/'],
            'slug_impact_acknowledged' => ['nullable', 'boolean'],
            // Lifecycle, schema and direct positions are owned by explicit actions, never by this form.
            'status' => ['prohibited'], 'schema_status' => ['prohibited'], 'position' => ['prohibited'], 'schema_revision' => ['prohibited'],
        ];
    }

    public function saveData(): CentralCategorySaveData
    {
        $parentId = $this->validated('parent_id');
        $expectedParentId = $this->validated('expected_parent_id');
        $fingerprint = $this->validated('slug_impact_fingerprint');
        /** @var list<int|string> $siblings */
        $siblings = $this->validated('sibling_ids');

        return new CentralCategorySaveData(
            name: (string) $this->validated('name'),
            slug: (string) $this->validated('slug'),
            parentId: $parentId === null ? null : (int) $parentId,
            expected: new CentralCategoryExpectedState(
                (string) $this->validated('expected_name'),
                (string) $this->validated('expected_slug'),
                $expectedParentId === null ? null : (int) $expectedParentId,
            ),
            originalScopeRevision: (int) $this->validated('current_scope_revision'),
            destinationScopeRevision: (int) $this->validated('destination_scope_revision'),
            siblingIds: array_map(intval(...), $siblings),
            displayPosition: (int) $this->validated('display_position'),
            slugImpactFingerprint: is_string($fingerprint) ? $fingerprint : null,
            slugImpactAcknowledged: (bool) $this->validated('slug_impact_acknowledged'),
        );
    }

    /**
     * Edit destination after a rejected save. A submitted slug that differs from the
     * saved one is carried as `slug_preview` so its routing impact renders server-side.
     */
    public function editUrl(): string
    {
        $category = $this->route('category');
        $slug = $this->input('slug');
        $preview = $category instanceof CentralCategory && is_string($slug) && $slug !== '' && mb_strlen($slug) <= 255 && $slug !== $category->slug ? $slug : null;

        return route('central.categories.edit', array_filter([
            'category' => $category instanceof CentralCategory ? $category->id : $category,
            'slug_preview' => $preview,
        ], static fn (mixed $value): bool => $value !== null));
    }

    protected function getRedirectUrl(): string
    {
        return $this->editUrl();
    }
}
