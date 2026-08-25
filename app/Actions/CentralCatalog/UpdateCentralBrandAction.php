<?php

namespace App\Actions\CentralCatalog;

use App\Actions\CentralCatalog\Concerns\ValidatesCentralBrandInput;
use App\Data\CentralCatalog\CentralBrandInput;
use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateCentralBrandAction
{
    use ValidatesCentralBrandInput;

    private const AUDITED_FIELDS = ['name', 'slug', 'website_url', 'country_code'];

    public function __construct(private AuditRecorder $audit) {}

    public function handle(User $actor, CentralBrand $brand, CentralBrandInput $input): CentralBrand
    {
        /** @var array{name: string, normalized_name: string, normalized_name_hash: string, slug: string, website_url: string|null, country_code: string|null}|null $validated */
        $validated = null;

        try {
            return DB::transaction(function () use ($actor, $brand, $input, &$validated): CentralBrand {
                $lockedBrand = CentralBrand::query()->lockForUpdate()->findOrFail($brand->getKey());
                $validated = $this->validatedBrandInput($input, $lockedBrand);
                $before = $lockedBrand->only(self::AUDITED_FIELDS);
                $lockedBrand->forceFill($validated)->saveOrFail();
                $after = $lockedBrand->only(self::AUDITED_FIELDS);
                $changedFields = array_keys(array_filter(
                    $after,
                    static fn (mixed $value, string $field): bool => $before[$field] !== $value,
                    ARRAY_FILTER_USE_BOTH,
                ));

                if ($changedFields !== []) {
                    $this->audit->record(
                        AuditAction::CatalogBrandUpdated,
                        AuditContext::Central,
                        $actor,
                        $lockedBrand,
                        null,
                        array_intersect_key($before, array_flip($changedFields)),
                        array_intersect_key($after, array_flip($changedFields)),
                    );
                }

                return $lockedBrand->refresh();
            });
        } catch (UniqueConstraintViolationException $exception) {
            $brand->refresh();

            if ($validated === null) {
                throw $exception;
            }

            $errors = $this->uniqueConstraintValidationErrors($validated, $brand);

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            throw $exception;
        }
    }
}
