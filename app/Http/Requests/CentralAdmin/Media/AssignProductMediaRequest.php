<?php

namespace App\Http\Requests\CentralAdmin\Media;

use App\Data\Media\AssignMediaToProductData;
use App\Support\Media\MediaAssignmentRoles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AssignProductMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'media_asset_id' => ['required', 'integer', Rule::exists('media_assets', 'id')],
            'role' => ['required', 'string', Rule::in(MediaAssignmentRoles::ALL)],
            'locale' => ['nullable', 'string', 'max:20', 'regex:'.MediaAssignmentRoles::LOCALE_PATTERN],
            'site_id' => ['nullable', 'integer', 'min:1'],
            'market_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function assignmentData(): AssignMediaToProductData
    {
        $data = $this->validated();

        return new AssignMediaToProductData(
            mediaAssetId: (int) $data['media_asset_id'],
            role: (string) $data['role'],
            locale: is_string($data['locale'] ?? null) ? $data['locale'] : null,
            siteId: isset($data['site_id']) ? (int) $data['site_id'] : null,
            marketId: isset($data['market_id']) ? (int) $data['market_id'] : null,
        );
    }
}
