<?php

namespace App\Http\Requests\CentralAdmin\Media;

use App\Data\Media\ProductMediaPreviewData;
use App\Support\Media\MediaAssignmentRoles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ProductMediaPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'preview_role' => ['nullable', 'string', Rule::in(MediaAssignmentRoles::ALL)],
            'preview_locale' => ['nullable', 'string', 'max:20', 'regex:'.MediaAssignmentRoles::LOCALE_PATTERN],
            'preview_site_id' => ['nullable', 'integer', 'min:1'],
            'preview_market_id' => ['nullable', 'integer', 'min:1'],
            'media_search' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function previewData(): ProductMediaPreviewData
    {
        $data = $this->validated();

        return new ProductMediaPreviewData(
            role: is_string($data['preview_role'] ?? null) ? $data['preview_role'] : 'main',
            locale: is_string($data['preview_locale'] ?? null) ? $data['preview_locale'] : null,
            siteId: isset($data['preview_site_id']) ? (int) $data['preview_site_id'] : null,
            marketId: isset($data['preview_market_id']) ? (int) $data['preview_market_id'] : null,
            mediaSearch: trim(is_string($data['media_search'] ?? null) ? $data['media_search'] : ''),
        );
    }
}
