<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AssignExistingCentralBrandLogoRequest extends FormRequest
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
        ];
    }

    public function mediaAssetId(): int
    {
        return (int) $this->validated('media_asset_id');
    }
}
