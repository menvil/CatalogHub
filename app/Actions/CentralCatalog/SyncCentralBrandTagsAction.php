<?php

declare(strict_types=1);

namespace App\Actions\CentralCatalog;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Models\CentralCatalog\CatalogTag;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Support\Normalization\CatalogTagNormalizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final readonly class SyncCentralBrandTagsAction
{
    public function __construct(
        private ResolveCatalogTagAction $resolveTag,
        private AuditRecorder $audit,
    ) {}

    /**
     * @param  list<string>  $tagNames
     *
     * @throws ValidationException
     */
    public function handle(User $actor, CentralBrand $brand, array $tagNames): CentralBrand
    {
        $normalizedNames = $this->normalizedUniqueNames($tagNames);

        return DB::transaction(function () use ($actor, $brand, $normalizedNames): CentralBrand {
            $lockedBrand = CentralBrand::query()->lockForUpdate()->findOrFail($brand->getKey());
            $before = $lockedBrand->tags()->get();

            $desired = collect($normalizedNames)
                ->map(fn (string $name): CatalogTag => $this->resolveTag->handle($name));

            $beforeIds = $before->modelKeys();
            $desiredIds = $desired
                ->map(static fn (CatalogTag $tag): int => (int) $tag->getKey())
                ->all();
            sort($beforeIds);
            sort($desiredIds);

            if ($beforeIds === $desiredIds) {
                return $lockedBrand->load('tags');
            }

            $lockedBrand->tags()->sync($desiredIds);
            $after = $lockedBrand->tags()->get();

            $this->audit->record(
                AuditAction::CatalogBrandTagsUpdated,
                AuditContext::Central,
                $actor,
                $lockedBrand,
                null,
                ['tags' => $this->sortedDisplayNames($before)],
                ['tags' => $this->sortedDisplayNames($after)],
            );

            return $lockedBrand->setRelation('tags', $after);
        });
    }

    /**
     * @param  list<string>  $tagNames
     * @return list<string>
     */
    private function normalizedUniqueNames(array $tagNames): array
    {
        $validator = Validator::make(
            ['tags' => $tagNames],
            [
                'tags' => ['present', 'array', 'max:20'],
                'tags.*' => [
                    'bail',
                    'required',
                    'string',
                    'max:80',
                    static function (string $attribute, mixed $value, \Closure $fail): void {
                        if (! is_string($value) || preg_match('/\p{Cc}/u', $value) === 1) {
                            $fail('Tags cannot contain control characters or newlines.');

                            return;
                        }

                        if (CatalogTagNormalizer::name($value) === '') {
                            $fail('Tags cannot be blank.');
                        }
                    },
                ],
            ],
            ['tags.max' => 'A Brand may have at most 20 tags.'],
        );

        $validator->validate();

        /** @var array<string, array{name: string, identity: string}> $unique */
        $unique = [];
        foreach ($tagNames as $tagName) {
            $name = CatalogTagNormalizer::name($tagName);
            $identity = CatalogTagNormalizer::identity($name);
            $hash = hash('sha256', $identity);
            if (! isset($unique[$hash])) {
                $unique[$hash] = ['name' => $name, 'identity' => $identity];
            }
        }

        uasort($unique, static fn (array $left, array $right): int => $left['identity'] <=> $right['identity']);

        return array_values(array_map(static fn (array $tag): string => $tag['name'], $unique));
    }

    /** @param Collection<int, CatalogTag> $tags */
    private function sortedDisplayNames(Collection $tags): array
    {
        return $tags
            ->sortBy([
                [static fn (CatalogTag $tag): string => (string) $tag->normalized_name, 'asc'],
                [static fn (CatalogTag $tag): int => (int) $tag->getKey(), 'asc'],
            ])
            ->pluck('name')
            ->values()
            ->all();
    }
}
