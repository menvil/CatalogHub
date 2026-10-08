<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin;

use App\Data\CentralCatalog\CategoryListFiltersData;
use App\Enums\CategorySchemaStatus;
use App\Enums\CentralCategoryStatus;
use App\Enums\Permission;
use App\Enums\SiteStatus;
use App\Services\Categories\CategoryAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CentralCategoryListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(CategoryAccess::class)->allows(Permission::CatalogCategoriesManage, $this->isMethod('POST'));
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'context' => $this->isMethod('POST') ? ['nullable', Rule::in(['detail'])] : ['prohibited'],
            'expected_status' => $this->isMethod('POST') ? ['nullable', Rule::enum(CentralCategoryStatus::class)] : ['prohibited'],
            'confirmed' => $this->routeIs('central.categories.archive') ? ['required', 'accepted'] : ['prohibited'],
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(CentralCategoryStatus::class)],
            'schema' => ['nullable', Rule::enum(CategorySchemaStatus::class)],
            'level' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'locale' => ['nullable', 'integer', 'min:1', Rule::exists('locales', 'id')->where('is_active', true)],
            'site' => ['nullable', 'integer', 'min:1', Rule::exists('sites', 'id')->whereNull('deleted_at')->whereNot('status', SiteStatus::Archived->value)],
            'translation' => ['nullable', Rule::in(['covered', 'missing', 'outdated'])],
            'sort' => ['nullable', Rule::in(['name', 'products', 'attributes', 'facets', 'sites', 'status', 'schema_status', 'updated_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', Rule::in([20, 50, 100])],
            'page' => ['nullable', 'integer', 'min:1', 'max:2147483647'],
        ];
    }

    /** @return array<string, mixed> */
    public function queryParameters(): array
    {
        return array_diff_key($this->validated(), array_flip(['confirmed', 'context', 'expected_status']));
    }

    public function filters(): CategoryListFiltersData
    {
        $data = $this->validated();

        return new CategoryListFiltersData(
            search: isset($data['q']) && trim($data['q']) !== '' ? trim($data['q']) : null,
            status: $data['status'] ?? null,
            schemaStatus: $data['schema'] ?? null,
            level: isset($data['level']) ? (int) $data['level'] : null,
            localeId: isset($data['locale']) ? (int) $data['locale'] : null,
            siteId: isset($data['site']) ? (int) $data['site'] : null,
            translation: $data['translation'] ?? null,
            sort: $data['sort'] ?? 'name',
            direction: $data['direction'] ?? 'asc',
            perPage: isset($data['per_page']) ? (int) $data['per_page'] : 20,
        );
    }
}
