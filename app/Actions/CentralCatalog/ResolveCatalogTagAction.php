<?php

declare(strict_types=1);

namespace App\Actions\CentralCatalog;

use App\Models\CentralCatalog\CatalogTag;
use App\Support\Normalization\CatalogTagNormalizer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

final class ResolveCatalogTagAction
{
    public function handle(string $label): CatalogTag
    {
        $name = CatalogTagNormalizer::name($label);
        if ($name === '' || mb_strlen($label) > 80 || preg_match('/\p{Cc}/u', $label) === 1) {
            throw ValidationException::withMessages([
                'tag' => 'A Tag must be nonblank, at most 80 characters, and contain no control characters or newlines.',
            ]);
        }

        $identity = CatalogTagNormalizer::identity($name);
        $hash = CatalogTagNormalizer::identityHash($name);

        $existing = CatalogTag::query()->where('normalized_name_hash', $hash)->first();

        if ($existing instanceof CatalogTag) {
            return $this->assertIdentity($existing, $identity);
        }

        try {
            // The nested transaction is a savepoint when called by the sync action. This
            // lets PostgreSQL recover from the unique race before the canonical refetch.
            return DB::transaction(function () use ($name, $identity, $hash): CatalogTag {
                $tag = new CatalogTag;
                $tag->forceFill([
                    'name' => $name,
                    'normalized_name' => $identity,
                    'normalized_name_hash' => $hash,
                ])->saveOrFail();

                return $tag;
            }, attempts: 1);
        } catch (UniqueConstraintViolationException $exception) {
            // A locking read observes the winning insert under MariaDB's default
            // REPEATABLE READ isolation, even when this transaction took an older
            // consistent snapshot before it lost the unique-key race.
            $existing = CatalogTag::query()
                ->where('normalized_name_hash', $hash)
                ->lockForUpdate()
                ->first();

            if (! $existing instanceof CatalogTag) {
                throw $exception;
            }

            return $this->assertIdentity($existing, $identity);
        }
    }

    private function assertIdentity(CatalogTag $tag, string $identity): CatalogTag
    {
        if (! hash_equals((string) $tag->normalized_name, $identity)) {
            throw new LogicException('A Catalog Tag normalized identity hash collision was detected.');
        }

        return $tag;
    }
}
