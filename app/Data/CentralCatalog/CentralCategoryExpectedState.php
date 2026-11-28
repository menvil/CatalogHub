<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Validation\ValidationException;

/**
 * Persisted Category identity and parent that an editor last read.
 *
 * Compared against the row-locked Category so a stale form cannot silently
 * overwrite a concurrent identity change or describe a Category that moved.
 * These are expected-state tokens, never permission checks.
 */
final readonly class CentralCategoryExpectedState
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?int $parentId,
    ) {}

    public function assertMatches(CentralCategory $locked): void
    {
        if ($locked->parent_id !== $this->parentId) {
            throw ValidationException::withMessages(['parent_id' => 'Category parent changed. Reload before saving.']);
        }
        if ($locked->name !== $this->name || $locked->slug !== $this->slug) {
            throw ValidationException::withMessages(['category' => 'This Category was changed since the form was opened. Reload before saving.']);
        }
    }
}
