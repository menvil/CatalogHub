<?php

namespace App\Actions\Translations;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MeasurementUnit;
use App\Services\Translations\TranslationSourceHashService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final readonly class DetectOutdatedTranslationsAction
{
    public function __construct(
        private TranslationSourceHashService $hashService,
    ) {}

    public function handle(Model $entity): int
    {
        $currentHash = $this->hashFor($entity);
        $count = 0;

        /** @var HasMany<Model, Model> $translations */
        $translations = $entity->translations();

        foreach ($translations->get() as $translation) {
            if ($translation->getAttribute('source_hash') === null) {
                continue;
            }

            if ($translation->getAttribute('source_hash') === $currentHash) {
                continue;
            }

            $translation->setAttribute('status', TranslationStatus::Outdated);
            $translation->save();
            $count++;
        }

        return $count;
    }

    private function hashFor(Model $entity): string
    {
        return match (true) {
            $entity instanceof CentralProduct => $this->hashService->forProduct($entity),
            $entity instanceof CentralCategory => $this->hashService->forCategory($entity),
            $entity instanceof AttributeDefinition => $this->hashService->forAttribute($entity),
            $entity instanceof AttributeSection => $this->hashService->forAttributeSection($entity),
            $entity instanceof AttributeOption => $this->hashService->forAttributeOption($entity),
            $entity instanceof MeasurementUnit => $this->hashService->forUnit($entity),
            default => throw new \InvalidArgumentException('Unsupported translatable entity: '.$entity::class),
        };
    }
}
