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

        return match (true) {
            $entity instanceof CentralProduct,
            $entity instanceof CentralCategory,
            $entity instanceof AttributeDefinition,
            $entity instanceof AttributeSection,
            $entity instanceof AttributeOption,
            $entity instanceof MeasurementUnit => $this->markRelationOutdated($entity->translations(), $currentHash),
            default => throw new \InvalidArgumentException('Unsupported translatable entity: '.$entity::class),
        };
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

    private function markRelationOutdated(HasMany $translations, string $currentHash): int
    {
        return $translations
            ->whereNotNull('source_hash')
            ->where('source_hash', '!=', $currentHash)
            ->update(['status' => TranslationStatus::Outdated]);
    }
}
