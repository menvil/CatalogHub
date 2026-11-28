<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

use Illuminate\Validation\ValidationException;

/**
 * One CA-018 Edit submission: identity, parent placement and sibling order intent
 * plus the expected-state tokens read when the form was loaded.
 */
final readonly class CentralCategorySaveData
{
    /**
     * @param  list<int>  $siblingIds  Sibling order of the original parent scope as shown to the editor.
     */
    public function __construct(
        public string $name,
        public string $slug,
        public ?int $parentId,
        public CentralCategoryExpectedState $expected,
        public int $originalScopeRevision,
        public int $destinationScopeRevision,
        public array $siblingIds,
        public int $displayPosition,
        public ?string $slugImpactFingerprint = null,
        public bool $slugImpactAcknowledged = false,
    ) {}

    public function changesParent(): bool
    {
        return $this->parentId !== $this->expected->parentId;
    }

    public function slugChanges(): bool
    {
        return $this->slug !== $this->expected->slug;
    }

    /**
     * Complete sibling order requested for the original parent scope, or null when unchanged.
     *
     * @return list<int>|null
     */
    public function requestedOrder(int $categoryId): ?array
    {
        $current = array_search($categoryId, $this->siblingIds, true);
        if ($current === false || $this->displayPosition >= count($this->siblingIds)) {
            throw ValidationException::withMessages(['display_position' => 'The sibling order changed. Reload before saving.']);
        }
        if ($current === $this->displayPosition) {
            return null;
        }
        if ($this->changesParent()) {
            throw ValidationException::withMessages(['display_position' => 'Change the parent or the display order in one save, not both. Moving a Category appends it to the end of its new parent.']);
        }
        $order = array_values(array_filter($this->siblingIds, static fn (int $id): bool => $id !== $categoryId));
        array_splice($order, $this->displayPosition, 0, [$categoryId]);

        return $order;
    }
}
