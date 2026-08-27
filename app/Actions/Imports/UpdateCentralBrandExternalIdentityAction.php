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
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final readonly class UpdateCentralBrandExternalIdentityAction
{
    public function __construct(
        private CentralBrandExternalIdentityResolver $resolver,
        private AuditRecorder $audit,
    ) {}

    public function handle(
        User $actor,
        CentralBrand $brand,
        CentralBrandExternalIdentity $identity,
        string $externalId,
        ?string $externalUrl,
    ): CentralBrandExternalIdentity {
        $normalizedId = ExternalIdentityNormalizer::externalId($externalId);
        $normalizedUrl = ExternalIdentityNormalizer::externalUrl($externalUrl);
        $hash = ExternalIdentityNormalizer::hash($normalizedId);

        return DB::transaction(function () use ($actor, $brand, $identity, $normalizedId, $normalizedUrl, $hash): CentralBrandExternalIdentity {
            $lockedBrand = CentralBrand::query()->lockForUpdate()->findOrFail($brand->getKey());
            $lockedIdentity = CentralBrandExternalIdentity::query()->lockForUpdate()->findOrFail($identity->getKey());
            $this->assertOwnership($lockedBrand, $lockedIdentity);
            $source = ImportSource::query()->findOrFail($lockedIdentity->import_source_id);

            $conflict = CentralBrandExternalIdentity::query()
                ->where('import_source_id', $source->getKey())
                ->where('external_id_hash', $hash)
                ->where('id', '!=', $lockedIdentity->getKey())
                ->first();

            if ($conflict instanceof CentralBrandExternalIdentity) {
                $this->resolver->assertExact($conflict, $normalizedId);
                throw $this->conflict($source, $normalizedId);
            }

            $beforeId = (string) $lockedIdentity->external_id;
            $beforeUrl = $lockedIdentity->external_url === null ? null : (string) $lockedIdentity->external_url;

            if ($beforeId === $normalizedId && $beforeUrl === $normalizedUrl) {
                return $lockedIdentity->setRelation('source', $source);
            }

            try {
                DB::transaction(function () use ($lockedIdentity, $normalizedId, $normalizedUrl, $hash): void {
                    $lockedIdentity->forceFill([
                        'external_id' => $normalizedId,
                        'external_id_hash' => $hash,
                        'external_url' => $normalizedUrl,
                    ])->saveOrFail();
                }, attempts: 1);
            } catch (UniqueConstraintViolationException $exception) {
                $conflict = CentralBrandExternalIdentity::query()
                    ->where('import_source_id', $source->getKey())
                    ->where('external_id_hash', $hash)
                    ->lockForUpdate()
                    ->first();

                if (! $conflict instanceof CentralBrandExternalIdentity) {
                    throw $exception;
                }

                $this->resolver->assertExact($conflict, $normalizedId);
                throw $this->conflict($source, $normalizedId);
            }

            $before = ['source_code' => (string) $source->code];
            $after = ['source_code' => (string) $source->code];
            if ($beforeId !== $normalizedId) {
                $before['external_id'] = $beforeId;
                $after['external_id'] = $normalizedId;
            }
            if ($beforeUrl !== $normalizedUrl) {
                $before['external_url'] = $beforeUrl;
                $after['external_url'] = $normalizedUrl;
            }

            $this->audit->record(
                AuditAction::CatalogBrandExternalIdentityUpdated,
                AuditContext::Central,
                $actor,
                $lockedBrand,
                null,
                $before,
                $after,
            );

            return $lockedIdentity->setRelation('source', $source);
        });
    }

    private function assertOwnership(CentralBrand $brand, CentralBrandExternalIdentity $identity): void
    {
        if ((int) $identity->central_brand_id !== (int) $brand->getKey()) {
            throw (new ModelNotFoundException)->setModel(CentralBrandExternalIdentity::class, [$identity->getKey()]);
        }
    }

    private function conflict(ImportSource $source, string $externalId): ExternalIdentityConflictException
    {
        return new ExternalIdentityConflictException("{$source->name} / {$externalId} is already linked to another Brand.");
    }
}
