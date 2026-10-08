<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin;

use App\Enums\Permission;
use App\Services\Categories\CategoryAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CentralCategoryDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(CategoryAccess::class)->allows(Permission::CatalogCategoriesManage);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['locale' => ['nullable', 'integer', 'min:1', Rule::exists('locales', 'id')->where('is_active', true)]];
    }

    public function localeId(): ?int
    {
        return $this->validated('locale') === null ? null : (int) $this->validated('locale');
    }
}
