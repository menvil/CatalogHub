<?php

declare(strict_types=1);

namespace App\View\Components\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use InvalidArgumentException;

final class DataTable extends Component
{
    /**
     * @param  list<array{key?: string, label?: string, align?: string}>  $columns
     * @param  list<array<string, mixed>>  $rows
     */
    public function __construct(
        public readonly string $caption,
        public readonly array $columns,
        public readonly array $rows,
        public readonly string $rowKey = 'id',
        public readonly string $empty = 'No records found.',
        public readonly bool $selectable = false,
        public readonly ?string $tableId = null,
    ) {
        $this->validateColumns();
        $this->validateRows();
    }

    public function render(): View
    {
        return view('components.admin.data-table');
    }

    private function validateColumns(): void
    {
        if ($this->columns === []) {
            throw new InvalidArgumentException('Data table columns cannot be empty.');
        }

        $keys = [];

        foreach ($this->columns as $column) {
            $key = trim((string) ($column['key'] ?? ''));
            $label = trim((string) ($column['label'] ?? ''));
            $align = (string) ($column['align'] ?? 'start');

            if ($key === '' || $label === '') {
                throw new InvalidArgumentException('Data table columns require a key and label.');
            }

            if (! in_array($align, ['start', 'center', 'end'], true)) {
                throw new InvalidArgumentException("Unsupported data table alignment [{$align}].");
            }

            $keys[] = $key;
        }

        if (count($keys) !== count(array_unique($keys))) {
            throw new InvalidArgumentException('Data table column keys must be unique.');
        }
    }

    private function validateRows(): void
    {
        $identifiers = [];

        foreach ($this->rows as $row) {
            $identifier = $row[$this->rowKey] ?? null;

            if (! is_string($identifier) && ! is_int($identifier)) {
                throw new InvalidArgumentException("Data table rows require a scalar [{$this->rowKey}] identifier.");
            }

            $identifier = trim((string) $identifier);

            if ($identifier === '') {
                throw new InvalidArgumentException("Data table rows require a non-empty [{$this->rowKey}] identifier.");
            }

            $identifiers[] = $identifier;
        }

        if (count($identifiers) !== count(array_unique($identifiers))) {
            throw new InvalidArgumentException('Data table row identifiers must be unique.');
        }
    }
}
