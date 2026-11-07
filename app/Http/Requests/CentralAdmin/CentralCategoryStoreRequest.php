<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin;

use Illuminate\Foundation\Http\FormRequest;

/** CA-018 Create: canonical identity, optional parent and the destination scope revision token. */
final class CentralCategoryStoreRequest extends FormRequest
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
            'scope_revision' => ['required', 'integer', 'min:0'],
            // Lifecycle, schema and ordering are owned by explicit actions, never by this form.
            'status' => ['prohibited'], 'schema_status' => ['prohibited'], 'position' => ['prohibited'], 'schema_revision' => ['prohibited'],
        ];
    }

    /** @return array{name: string, slug: string, parent_id: int|null} */
    public function categoryData(): array
    {
        $parentId = $this->validated('parent_id');

        return [
            'name' => (string) $this->validated('name'),
            'slug' => (string) $this->validated('slug'),
            'parent_id' => $parentId === null ? null : (int) $parentId,
        ];
    }

    public function scopeRevision(): int
    {
        return (int) $this->validated('scope_revision');
    }
}
