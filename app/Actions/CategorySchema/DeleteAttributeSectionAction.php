<?php

namespace App\Actions\CategorySchema;

use App\Exceptions\CategorySchema\CannotDeleteAttributeSectionException;
use App\Models\CentralCatalog\AttributeSection;
use Illuminate\Support\Facades\DB;

final class DeleteAttributeSectionAction
{
    public function handle(AttributeSection $section): void
    {
        DB::transaction(function () use ($section): void {
            /** @var AttributeSection $lockedSection */
            $lockedSection = $section->newQuery()->whereKey($section->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedSection->attributes()->exists()) {
                throw CannotDeleteAttributeSectionException::hasAttributes();
            }

            if ($lockedSection->children()->exists()) {
                throw CannotDeleteAttributeSectionException::hasChildren();
            }

            $lockedSection->delete();
        });
    }
}
