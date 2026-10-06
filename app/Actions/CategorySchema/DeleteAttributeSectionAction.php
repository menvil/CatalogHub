<?php

namespace App\Actions\CategorySchema;

use App\Enums\SchemaMutationOrigin;
use App\Exceptions\CategorySchema\CannotDeleteAttributeSectionException;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\User;
use App\Services\CategorySchema\SchemaRevision;
use Illuminate\Support\Facades\DB;

final class DeleteAttributeSectionAction
{
    public function handle(AttributeSection $section, ?User $actor = null): void
    {
        $categoryId = $section->central_category_id;
        app(SchemaRevision::class)->mutate($categoryId, SchemaMutationOrigin::SectionDeleted, $section->id, function () use ($section): void {
            $this->perform($section);
        }, $actor);
    }

    private function perform(AttributeSection $section): void
    {
        $section = AttributeSection::query()->findOrFail($section->id);

        DB::transaction(function () use ($section): void {
            /** @var AttributeSection $lockedSection */
            $lockedSection = $section->newQuery()->whereKey($section->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedSection->assignments()->exists()) {
                throw CannotDeleteAttributeSectionException::hasAttributes();
            }

            if ($lockedSection->children()->exists()) {
                throw CannotDeleteAttributeSectionException::hasChildren();
            }

            $lockedSection->delete();
        });
    }
}
