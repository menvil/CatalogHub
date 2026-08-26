<?php

declare(strict_types=1);

namespace App\Actions\Imports;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Exceptions\Imports\ExternalIdentityConflictException;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Imports\CentralBrandExternalIdentity;
use App\Models\Imports\ImportSource;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Imports\CentralBrandExternalIdentityResolver;
use App\Support\Imports\ExternalIdentityNormalizer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class LinkCentralBrandExternalIdentityAction
{
    public function __construct(
        private CentralBrandExternalIdentityResolver $resolver,
        private AuditRecorder $audit,
    ) {}

    public function handle(
        User $actor,
        CentralBrand $brand,
        ImportSource $source,
        string $externalId,
        ?string $externalUrl,
    ): CentralBrandExternalIdentity {
        $normalizedId = ExternalIdentityNormalizer::externalId($externalId);
        $normalizedUrl = ExternalIdentityNormalizer::externalUrl($externalUrl);
        $hash = ExternalIdentityNormalizer::hash($normalizedId);

        return DB::transaction(function () use ($actor, $brand, $source, $normalizedId, $normalizedUrl, $hash): CentralBrandExternalIdentity {
            $lockedBrand = CentralBrand::query()->lockForUpdate()->findOrFail($brand->getKey());
            $currentSource = ImportSource::query()->findOrFail($source->getKey());

            if (! $currentSource->isActive()) {
                throw ValidationException::withMessages([
                    'import_source_id' => 'New external identities require an active import source.',
                ]);
            }

            $existing = $this->identityByHash($currentSource, $hash);
            if ($existing instanceof CentralBrandExternalIdentity) {
                $this->resolver->assertExact($existing, $normalizedId);

                if ((int) $existing->central_brand_id === (int) $lockedBrand->getKey()) {
                    return $existing;
                }

                throw $this->conflict($currentSource, $normalizedId);
            }

            try {
                $identity = DB::transaction(function () use ($lockedBrand, $currentSource, $normalizedId, $normalizedUrl, $hash): CentralBrandExternalIdentity {
                    $identity = new CentralBrandExternalIdentity;
                    $identity->forceFill([
                        'central_brand_id' => $lockedBrand->getKey(),
                        'import_source_id' => $currentSource->getKey(),
                        'external_id' => $normalizedId,
                        'external_id_hash' => $hash,
                        'external_url' => $normalizedUrl,
                    ])->saveOrFail();

                    return $identity;
                }, attempts: 1);
            } catch (UniqueConstraintViolationException $exception) {
                $existing = $this->identityByHash($currentSource, $hash, lock: true);
                if (! $existing instanceof CentralBrandExternalIdentity) {
                    throw $exception;
                }

                $this->resolver->assertExact($existing, $normalizedId);
                if ((int) $existing->central_brand_id === (int) $lockedBrand->getKey()) {
                    return $existing;
                }

                throw $this->conflict($currentSource, $normalizedId);
            }

            $this->audit->record(
                AuditAction::CatalogBrandExternalIdentityLinked,
                AuditContext::Central,
                $actor,
                $lockedBrand,
                null,
                null,
                $this->snapshot($currentSource, $identity),
            );

            return $identity->setRelation('source', $currentSource);
        });
    }

    private function identityByHash(ImportSource $source, string $hash, bool $lock = false): ?CentralBrandExternalIdentity
    {
        $query = CentralBrandExternalIdentity::query()
            ->where('import_source_id', $source->getKey())
            ->where('external_id_hash', $hash);

        return ($lock ? $query->lockForUpdate() : $query)->first();
    }

    private function conflict(ImportSource $source, string $externalId): ExternalIdentityConflictException
    {
        return new ExternalIdentityConflictException("{$source->name} / {$externalId} is already linked to another Brand.");
    }

    /** @return array{source_code: string, external_id: string, external_url: string|null} */
    private function snapshot(ImportSource $source, CentralBrandExternalIdentity $identity): array
    {
        return [
            'source_code' => (string) $source->code,
            'external_id' => (string) $identity->external_id,
            'external_url' => $identity->external_url === null ? null : (string) $identity->external_url,
        ];
    }
}
