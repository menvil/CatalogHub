<?php

namespace App\Actions\Corrections;

use App\Enums\ChangeRequestStatus;
use App\Exceptions\Corrections\CannotCreateCorrectionException;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ChangeRequest;
use App\Models\SiteProduct;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Validator;

final class CreateCorrectionRequestAction
{
    /** @var array<string, string> */
    public const PRODUCT_FIELDS = [
        'name' => 'Canonical name',
        'model' => 'Model',
        'slug' => 'Canonical slug',
    ];

    public function handle(
        User $creator,
        CentralProduct $product,
        string $fieldPath,
        mixed $proposedValue,
        ?string $evidenceUrl = null,
        ?string $evidenceNote = null,
    ): ChangeRequest {
        if (! $creator->hasCatalogHubPermission('corrections.request') || $creator->site_id === null) {
            throw new AuthorizationException('Only a site administrator can request a correction.');
        }

        if (! SiteProduct::query()
            ->where('site_id', $creator->site_id)
            ->where('central_product_id', $product->getKey())
            ->exists()) {
            throw CannotCreateCorrectionException::because('The product is not available in the administrator site.');
        }

        $data = Validator::make([
            'field_path' => $fieldPath,
            'proposed_value' => $proposedValue,
            'evidence_url' => $this->nullableText($evidenceUrl),
            'evidence_note' => $this->nullableText($evidenceNote),
        ], [
            'field_path' => ['required', 'string', 'in:'.implode(',', array_keys(self::PRODUCT_FIELDS))],
            'proposed_value' => ['required', 'string', 'max:10000'],
            'evidence_url' => ['nullable', 'url:http,https', 'max:2048'],
            'evidence_note' => ['nullable', 'string', 'max:5000'],
        ])->validate();

        return ChangeRequest::query()->create([
            'site_id' => $creator->site_id,
            'central_product_id' => $product->getKey(),
            'entity_type' => 'central_product',
            'entity_id' => $product->getKey(),
            'field_path' => $data['field_path'],
            'old_value_json' => ['value' => $product->getAttribute($data['field_path'])],
            'proposed_value_json' => ['value' => $data['proposed_value']],
            'evidence_url' => $data['evidence_url'],
            'evidence_note' => $data['evidence_note'],
            'status' => ChangeRequestStatus::Pending,
            'created_by_user_id' => $creator->getKey(),
            'metadata_json' => ['source' => 'site_admin'],
        ]);
    }

    private function nullableText(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }
}
